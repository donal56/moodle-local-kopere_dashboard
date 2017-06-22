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
 * @created    31/01/17 06:01
 * @package    local_kopere_dashboard
 * @copyright  2017 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_dashboard\util;

class Mensagem {
    public static function agendaMensagem($mensagem) {
        if (!isset($_SESSION['kopere_mensagem'])) {
            $_SESSION['kopere_mensagem'] = $mensagem;
        } else {
            $_SESSION['kopere_mensagem'] .= $mensagem;
        }
    }

    public static function getMensagemAgendada() {
        if (isset($_SESSION['kopere_mensagem'])) {
            $texto = $_SESSION['kopere_mensagem'];
            @$_SESSION['kopere_mensagem'] = null;
            if ($texto != null) {
                return $texto;
            }
        }
        return "";
    }

    public static function clearMensagem() {
        @$_SESSION['kopere_mensagem'] = null;
    }

    public static function warning($texto) {
        return "<div class=\"alert alert-warning\">
            <i class=\"fa fa-exclamation-circle\"></i>
            $texto
        </div>";
    }

    public static function printWarning($texto) {
        echo self::warning($texto);
    }

    public static function agendaMensagemWarning($texto) {
        self::agendaMensagem(self::warning($texto));
    }

    public static function success($texto) {
        return "<div class=\"alert alert-success\">
            <i class=\"fa fa-check-circle\"></i>
            $texto
        </div>";
    }

    public static function printSuccess($texto) {
        echo self::success($texto);
    }

    public static function agendaMensagemSuccess($texto) {
        self::agendaMensagem(self::success($texto));
    }

    public static function info($texto) {
        return "<div class=\"alert alert-info\">
            <i class=\"fa fa-info-circle\"></i>
            $texto
        </div>";
    }

    public static function printInfo($texto) {
        echo self::info($texto);
    }

    public static function agendaMensagemInfo($texto) {
        self::agendaMensagem(self::info($texto));
    }

    public static function danger($texto) {
        return "<div class=\"alert alert-danger\">
            <i class=\"fa fa-times-circle\"></i>
            $texto
        </div>";
    }

    public static function printDanger($texto) {
        echo self::danger($texto);
    }

    public static function agendaMensagemDanger($texto) {
        self::agendaMensagem(self::danger($texto));
    }
}