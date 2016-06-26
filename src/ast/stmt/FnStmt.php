<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2016 Marcelo Camargo <marcelocamargo@linuxmail.org> and
 * CONTRIBUTORS.
 *
 * This file is part of Quack.
 *
 * Quack is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Quack is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Quack.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace QuackCompiler\Ast\Stmt;

use \QuackCompiler\Parser\Parser;

class FnStmt implements Stmt
{
    public $name;
    public $by_reference;
    public $body;
    public $parameters;
    public $modifiers = [];

    public function __construct($name, $by_reference, $body, $parameters, $modifiers = [])
    {
        $this->name = $name;
        $this->by_reference = $by_reference;
        $this->body = $body;
        $this->parameters = $parameters;
        $this->modifiers = $modifiers;
    }

    public function format(Parser $parser)
    {
        $string_builder = ['fn '];
        if ($this->by_reference) {
            $string_builder[] = '*';
        }

        $string_builder[] = $this->name;
        if (sizeof($this->parameters) > 0) {
            $string_builder[] = ' [';

            for ($i = 0, $l = sizeof($this->parameters); $i < $l; $i++) {
                $string_builder[] = $this->parameters[$i]->format($parser);

                if ($i !== $l - 1) {
                    $string_builder[] = ', ';
                }
            }

            $string_builder[] = '] ';
        } else {
            $string_builder[] = '! ';
        }

        $string_builder[] = $this->body->format($parser);

        return implode($string_builder);
    }
}
