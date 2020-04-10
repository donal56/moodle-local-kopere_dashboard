<?php
/**
 * User: Eduardo Kraus
 * Date: 10/04/2020
 * Time: 18:05
 */

namespace local_kopere_dashboard;


use local_kopere_dashboard\html\data_table;
use local_kopere_dashboard\html\form;
use local_kopere_dashboard\html\inputs\input_select;
use local_kopere_dashboard\html\table_header_item;
use local_kopere_dashboard\util\dashboard_util;
use local_kopere_dashboard\util\datatable_search_util;

class access {

    public function dashboard() {
        dashboard_util::add_breadcrumb(get_string_kopere('useraccess_title'));
        dashboard_util::start_page();

        $ano = date('Y');
        $mes = date('m');
        $changue_mes = optional_param('changue_mes', "{$ano}-{$mes}", PARAM_TEXT);

        $ultimosMeses = array();
        for ($i = 0; $i < 24; $i++) {
            if ($mes < 10) {
                $mes = "0" . intval($mes);
            }
            $ultimosMeses[] = array('key' => "{$ano}-{$mes}", 'value' => "{$mes} / {$ano}");
            $mes--;
            if ($mes == 0) {
                $ano--;
                $mes = 12;
            }
        }
        $form = new form("?classname=access&method=dashboard");
        $form->add_input(input_select::new_instance()
            ->set_title('Selecione o Mês')
            ->set_name('changue_mes')
            ->set_values($ultimosMeses)
            ->set_value($changue_mes));
        $form->close();


        echo '<div class="element-box">';

        $table = new data_table();
        $table->add_header('#', 'userid', table_header_item::TYPE_INT, null, 'width: 20px');
        $table->add_header(get_string_kopere('user_table_fullname'), 'fullname');
        $table->add_header(get_string_kopere('user_table_email'), 'email');
        $table->add_header(get_string_kopere('user_table_phone'), 'phone1');
        $table->add_header(get_string_kopere('user_table_celphone'), 'phone2');
        $table->add_header(get_string_kopere('user_table_city'), 'city');

        $table->set_ajax_url("?classname=access&method=load_all_users&changue_mes={$changue_mes}");
        $table->set_click_redirect("?classname=users&method=details&userid={id}", 'id');
        $table->print_header();
        $table->close(true, array("order" => array(array(1, "asc"))));

        echo '</div>';
        dashboard_util::end_page();
        ?>
        <script>
            jQuery('#changue_mes').change(function() {
               location.href = "?classname=access&method=dashboard&changue_mes="+ $('#changue_mes').val()
            });
        </script><?php
    }

    public function load_all_users() {
        $changue_mes = required_param('changue_mes', PARAM_TEXT);

        $columns = array(
            'userid',
            'firstname',
            'username',
            'email',
            'phone1',
            'phone2',
            'city',
            'lastname'
        );
        $search = new datatable_search_util($columns);

        $search->execute_sql_and_return("
               SELECT {[columns]}
                 FROM {logstore_standard_log} l
                 JOIN {user}                  u ON l.userid = u.id
                WHERE action LIKE 'loggedin'
                  AND date_format( from_unixtime(l.timecreated), '%Y-%m' ) LIKE '{$changue_mes}'
            ", 'GROUP BY l.userid', null,
            'local_kopere_dashboard\util\user_util::column_fullname');

    }

}