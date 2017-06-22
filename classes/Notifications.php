<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @created    13/05/17 13:27
 * @package    local_kopere_dashboard
 * @copyright  2017 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_dashboard;

use core\event\base;
use local_kopere_dashboard\html\Botao;
use local_kopere_dashboard\html\DataTable;
use local_kopere_dashboard\html\Form;
use local_kopere_dashboard\html\inputs\InputSelect;
use local_kopere_dashboard\html\inputs\InputText;
use local_kopere_dashboard\html\TableHeaderItem;
use local_kopere_dashboard\html\TinyMce;
use local_kopere_dashboard\util\DashboardUtil;
use local_kopere_dashboard\util\Header;
use local_kopere_dashboard\util\Mensagem;
use local_kopere_dashboard\vo\kopere_dashboard_events;

class Notifications extends NotificationsUtil {
    public function dashboard() {
        global $CFG, $DB;

        DashboardUtil::startPage('Notificações', null, 'Notifications::settings');

        echo '<div class="element-box">';
        echo '<h3>Notificações</h3>';

        echo '<p>Receba notificações sempre que uma ação acontecer no Moodle.</p>';

        if (strlen(get_config('moodle', 'smtphosts')) < 5) {
            Mensagem::printDanger('<p>Este recurso precisa do SMTP configurado.</p>
                    <p><a href="https://moodle.eduardokraus.com/configurar-o-smtp-no-moodle"
                          target="_blank">Leia aqui como configurar o SMTP</a></p>
                    <p><a href="' . $CFG->wwwroot . '/admin/settings.php?section=messagesettingemail"
                          target="_blank">Clique aqui para configurar a saída de e-mail</a></p>');
        } else {
            Botao::add('Nova notificação', 'Notifications::add', '', true, false, true);

            $events = $DB->get_records('kopere_dashboard_events');
            $eventsList = array();
            foreach ($events as $event) {
                /** @var base $eventClass */
                $eventClass = $event->event;

                $event->module_name = $this->moduleName($event->module, false);
                $event->event_name = $eventClass::get_name();
                $event->acoes
                    = "<div class=\"text-center\">
                                    " . Botao::icon('edit', "Notifications::addSegundaEtapa&id={$event->id}", false) . "&nbsp;&nbsp;&nbsp;
                                    " . Botao::icon('delete', "Notifications::delete&id={$event->id}") . "
                                </div>";

                $eventsList[] = $event;
            }

            if ($events) {
                $table = new DataTable();
                $table->addHeader('#', 'id', TableHeaderItem::TYPE_INT);
                $table->addHeader('Módulo', 'module_name');
                $table->addHeader('Ação', 'event_name');
                $table->addHeader('Assunto', 'subject');
                $table->addHeader('Ativo', 'status', TableHeaderItem::RENDERER_VISIBLE);
                $table->addHeader('De', 'userfrom');
                $table->addHeader('Para', 'userto');
                $table->addHeader('', 'acoes', TableHeaderItem::TYPE_ACTION);

                // $table->setClickRedirect ( 'Users::details&userid={id}', 'id' );
                $table->printHeader();
                $table->setRow($events);
                $table->close();
            } else {
                Mensagem::printWarning('Nenhuma notificação!');
            }
        }

        echo '</div>';
        DashboardUtil::endPage();
    }

    public function add() {
        if (!AJAX_SCRIPT) {
            DashboardUtil::startPage(array(
                array('Notifications::dashboard', 'Notificações'),
                'Nova Notificações'
            ));
        } else {
            DashboardUtil::startPopup('Nova Notificações');
        }

        echo '<div class="element-box">';

        $events = $this->listEvents();
        $modulesList = array();
        foreach ($events->components as $components) {
            $moduleName = $this->moduleName($components, true);

            if ($moduleName != null) {
                $modulesList[] = array('key' => $components, 'value' => $moduleName);
            }
        }

        $form = new Form('Notifications::addSegundaEtapa');
        $form->addInput(
            InputSelect::newInstance()->setTitle('De qual módulo deseja receber notificação?')
                ->setName('module')
                ->setValues($modulesList)
                ->setAddSelecione(true)
                ->setDescription('Módulos/Atividades não utilizados não aparecem!')
        );
        echo '<div id="restante-form">Selecione o Módulo!</div>';
        $form->close();

        ?>
        <script>
            $('#module').change(function () {
                var data = {
                    module: $(this).val()
                };
                $('#restante-form').load('open-ajax-table.php?NotificationsUtil::addFormExtra', data);
            });
        </script>
        <?php

        echo '</div>';
        if (!AJAX_SCRIPT) {
            DashboardUtil::endPage();
        } else {
            DashboardUtil::endPopup();
        }
    }

    public function addSegundaEtapa() {
        global $CFG, $DB;

        /** @var base $eventClass */
        $eventClass = optional_param('event', '', PARAM_RAW);
        $module = optional_param('module', '', PARAM_RAW);
        $id = optional_param('id', 0, PARAM_INT);

        if ($id) {
            /** @var kopere_dashboard_events $evento */
            $evento = $DB->get_record('kopere_dashboard_events', array('id' => $id));
            Header::notfoundNull($evento, 'Notificação não localizado!');

            $eventClass = $evento->event;
            $module = $evento->module;

            DashboardUtil::startPage(array(
                array('Notifications::dashboard', 'Notificações'),
                'Editando Notificação'
            ));
            echo '<div class="element-box">';
            echo '<h3>Editando Notificação</h3>';
        } else {
            $evento = kopere_dashboard_events::createNew();
            DashboardUtil::startPage(array(
                array('Notifications::dashboard', 'Notificações'),
                'Nova Notificação'
            ));
            echo '<div class="element-box">';
            echo '<h3>Nova Notificação</h3>';
        }

        $form = new Form('Notifications::addSave');
        $form->createHiddenInput('id', $id);
        $form->createHiddenInput('event', $eventClass);
        $form->createHiddenInput('module', $module);

        $form->printRow(
            'De qual ação deseja receber notificações?',
            $eventClass::get_name());

        if ($id) {
            $status = array(
                array('key' => 1, 'value' => 'Ativo'),
                array('key' => 0, 'value' => 'Inativo')
            );
            $form->addInput(
                InputSelect::newInstance()->setTitle('Status da Notificação')
                    ->setName('status')
                    ->setValues($status)
                    ->setValue($evento->status)
                    ->setDescription('Se quiser interromper as notificações, marque como "Inativo" e salve!'));
        }

        $valueFrom = array(
            array('key' => 'admin', 'value' => 'Administrador do Site')
        );
        $form->addInput(
            InputSelect::newInstance()->setTitle('De')
                ->setName('userfrom')
                ->setValues($valueFrom)
                ->setValue($evento->userfrom)
                ->setDescription('Quem será o remetente da mensagem?'));

        $valueTo = array(
            array('key' => 'admin', 'value' => 'Administrador do Site (Somente o principal)'),
            array('key' => 'admins', 'value' => 'Administradores do Site (Todos os administradores)'),
            array('key' => 'teachers', 'value' => 'Professores do curso (Somente se for dentro de um curso)'),
            array('key' => 'student', 'value' => 'O Aluno (Envia ao próprio aluno que fez a ação)')
        );
        $form->addInput(
            InputSelect::newInstance()->setTitle('Para')
                ->setName('userto')
                ->setValues($valueTo)
                ->setValue($evento->userto)
                ->setDescription('Quem receberá estas mensagens?'));

        $form->addInput(
            InputText::newInstance()->setTitle('Assunto')
                ->setName('subject')
                ->setValue($evento->subject)
                ->setDescription('Assunto da mensagem'));

        $template = "{$CFG->dirroot}/local/kopere_dashboard/assets/mail/" . get_config('local_kopere_dashboard', 'notificacao-template');
        $templateContent = file_get_contents($template);

        if (!$id) {
            if (strpos($module, 'mod_') === 0) {
                $mailText = "{$CFG->dirroot}/local/kopere_dashboard/assets/mail-text/mod.html";
                $moduleName = get_string('modulename', $module);
            } else {
                $mailText = "{$CFG->dirroot}/local/kopere_dashboard/assets/mail-text/{$module}.html";
                $moduleName = '';
            }

            if (file_exists($mailText)) {
                $htmlText = file_get_contents($mailText);
            } else {
                $htmlText = '<p>Olá {[to.fullname]},</p><p>&nbsp;</p><p>Att,<br>{[from.fullname]}.</p>';
            }

            $htmlText = str_replace('{[event.name]}', $eventClass::get_name(), $htmlText);
            $htmlText = str_replace('{[module.name]}', $moduleName, $htmlText);
        } else {
            $htmlText = $evento->message;
        }

        $htmlTextarea = '<textarea name="message" id="message" style="height:500px">' . htmlspecialchars($htmlText) . '</textarea>';
        $templateContent = str_replace('{[message]}', $htmlTextarea, $templateContent);
        $form->printPanel('Mensagem', $templateContent);
        echo TinyMce::createInputEditor('#message');

        if ($id) {
            $form->createSubmitInput('Atualizar alerta');
        } else {
            $form->createSubmitInput('Criar alerta');
        }

        $form->close();

        echo '</div>';
        DashboardUtil::endPage();
    }

    public function addSave() {
        global $DB;

        $kopere_dashboard_events = kopere_dashboard_events::createNew();

        if ($kopere_dashboard_events->id) {
            $DB->update_record('kopere_dashboard_events', $kopere_dashboard_events);
        } else {
            $DB->insert_record('kopere_dashboard_events', $kopere_dashboard_events);
        }

        Mensagem::agendaMensagemSuccess('Notificação criada!');
        Header::location('Notifications::dashboard');
    }

    public function delete() {
        global $DB;

        $status = optional_param('status', '', PARAM_TEXT);
        $id = optional_param('id', 0, PARAM_INT);
        /** @var kopere_dashboard_events $event */
        $event = $DB->get_record('kopere_dashboard_events', array('id' => $id));
        Header::notfoundNull($event, 'Notificação não localizada!');

        if ($status == 'sim') {
            $DB->delete_records('kopere_dashboard_events', array('id' => $id));

            Mensagem::agendaMensagemSuccess('Notificação excluída com sucesso!');
            Header::location('Notifications::dashboard');
        }

        DashboardUtil::startPage(array(
            array('Notifications::dashboard', 'Notificações'),
            'Excluíndo Notificação'
        ));

        echo "<p>Deseja realmente excluir esta Notificação?</p>";
        Botao::delete('Sim', 'Notifications::delete&status=sim&id=' . $event->id, '', false);
        Botao::add('Não', 'Notifications::dashboard', 'margin-left-10', false);

        DashboardUtil::endPage();
    }

    public function settings() {
        global $CFG;
        ob_clean();
        DashboardUtil::startPopup('Configurações do e-mail', 'Settings::settingsSave');

        $form = new Form();

        $values = array();
        $templates = glob("{$CFG->dirroot}/local/kopere_dashboard/assets/mail/*.html");
        foreach ($templates as $template) {
            $values[] = array('key' => pathinfo($template, PATHINFO_BASENAME));
        }

        $form->addInput(
            InputSelect::newInstance()->setTitle('Template')
                ->setValues($values, 'key', 'key')
                ->setValueByConfig('notificacao-template'));

        $form->printPanel('Preview', "<div id=\"area-mensagem-preview\"></div>");

        $form->printRow('Templates estão na pasta', "{$CFG->dirroot}/local/kopere_dashboard/assets/mail/");

        $form->close();

        ?>
        <script>
            $('#notificacao-template').change(notificacaoTemplateChange);

            function notificacaoTemplateChange() {
                var data = {
                    template: $('#notificacao-template').val()
                };
                $('#area-mensagem-preview').load('open-ajax-table.php?Notifications::settingsLoadTemplate', data);
            }

            notificacaoTemplateChange();
        </script>
        <style>
            .table-messagem {
                max-width: 600px;
            }
        </style>
        <?php

        DashboardUtil::endPopup();
    }
}