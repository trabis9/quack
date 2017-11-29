<?php
/**
 * Quack Compiler and toolkit
 * Copyright (C) 2015-2017 Quack and CONTRIBUTORS
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
namespace QuackCompiler\Ast\Expr;

use \QuackCompiler\Ast\Expr;
use \QuackCompiler\Ast\Node;
use \QuackCompiler\Ds\Set;
use \QuackCompiler\Intl\Localization;
use \QuackCompiler\Parser\Parser;
use \QuackCompiler\Pretty\Parenthesized;
use \QuackCompiler\Scope\Symbol;
use \QuackCompiler\Scope\Meta;
use \QuackCompiler\Scope\Scope;
use \QuackCompiler\Scope\ScopeError;
use \QuackCompiler\Types\HindleyMilner;
use \QuackCompiler\Types\TypeVar;

class WhereExpr extends Node implements Expr
{
    use Parenthesized;

    public $expr;
    public $clauses;

    public function __construct(Expr $expr, $clauses)
    {
        $this->expr = $expr;
        $this->clauses = $clauses;
    }

    public function format(Parser $parser)
    {
        $first = true;
        $size = count($this->clauses);
        $processed = 0;

        $source = $this->expr->format($parser);
        $source .= PHP_EOL;

        $parser->openScope();

        $source .= $parser->indent();
        $source .= 'where ';

        foreach ($this->clauses as $clause) {
            $key = $clause[0];
            $value = $clause[1];

            $processed++;

            if (!$first) {
                $source .= $parser->indent();
                $source .= '    , ';
            } else {
                $first = false;
            }

            $source .= $key;
            $source .= ' :- ';
            $source .= $value->format($parser);

            if ($processed < $size) {
                $source .= PHP_EOL;
            }
        }

        $parser->closeScope();

        return $this->parenthesize($source);
    }

    public function injectScope($parent_scope)
    {
        $this->scope = new Scope($parent_scope);
        // Bind where-symbols
        foreach ($this->clauses as $clause) {
            list ($key, $value) = $clause;

            if ($this->scope->hasLocal($key)) {
                throw new ScopeError(Localization::message('SCO120', [$key]));
            }

            $value->injectScope($this->scope);
            $this->scope->insert($key, Symbol::S_VARIABLE | Symbol::S_INITIALIZED);
        }

        $this->expr->injectScope($this->scope);
    }

    public function analyze(Scope $scope, Set $non_generic)
    {
        list ($name, $defn) = $this->clauses[0];

        $new_type = new TypeVar();
        $new_env = clone $scope;
        $new_env->insert($name, Symbol::S_VARIABLE);
        $new_env->setMeta(Meta::M_TYPE, $name, $new_type);
        $new_non_generic = clone $non_generic;
        $new_non_generic->push($new_type);

        $defn_type = $defn->analyze($new_env, $new_non_generic);
        HindleyMilner::unify($new_type, $defn_type);

        return $this->expr->analyze($new_env, $non_generic);
    }
}
