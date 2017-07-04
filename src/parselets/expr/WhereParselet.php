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
namespace QuackCompiler\Parselets\Expr;

use \QuackCompiler\Ast\Expr\WhereExpr;
use \QuackCompiler\Ast\Expr\Expr;
use \QuackCompiler\Lexer\Token;
use \QuackCompiler\Parselets\InfixParselet;
use \QuackCompiler\Parser\Grammar;
use \QuackCompiler\Parser\Precedence;

class WhereParselet implements InfixParselet
{
    public function parse(Grammar $grammar, Expr $left, Token $token)
    {
        $clauses = [];

        $name = $grammar->identifier();
        $grammar->parser->match(':-');
        $value = $grammar->_expr();
        $clauses[] = [$name, $value];

        while ($grammar->parser->is(';')) {
            $grammar->parser->consume();
            $name = $grammar->identifier();
            $grammar->parser->match(':-');
            $value = $grammar->_expr();
            $clauses[] = [$name, $value];
        }

        return new WhereExpr($left, $clauses);
    }

    public function getPrecedence()
    {
        return Precedence::WHERE;
    }
}
