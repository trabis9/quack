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
use \QuackCompiler\Scope\Kind;
use \QuackCompiler\Scope\Meta;
use \QuackCompiler\Scope\ScopeError;

class BreakStmt extends Stmt
{
    public $label;
    public $is_explicit;

    public function __construct($label = null)
    {
        $this->label = $label;
        $this->is_explicit = null !== $label;
    }

    public function format(Parser $parser)
    {
        $source = 'break';

        if ($this->is_explicit) {
            $source .= ' ';
            $source .= $this->label;
        }

        $source .= PHP_EOL;
        return $source;
    }

    public function injectScope(&$parent_scope)
    {
        if (!$this->is_explicit) {
            // Check if there is an implicit label
            $label = $parent_scope->getMetaInContext(Meta::M_LABEL);

            // If there is no implicit labels in the context, then the user is
            // calling 'break' outsite a loop.
            if (null === $label) {
                throw new ScopeError([
                    'message' => "Called `break' outsite a loop"
                ]);
            }
        } else {
            // Assert that we are receiving a declared label
            $label = $parent_scope->lookup($this->label);

            if (($label & Kind::K_LABEL) !== Kind::K_LABEL) {
                // When the symbol exist, but it's not a label
                throw new ScopeError([
                    'message' => "Called `break' with invalid label `{$this->label}'"
                ]);
            }

            $refcount = &$parent_scope->getMeta(Meta::M_REF_COUNT, $this->label);
            if (null === $refcount) {
                $parent_scope->setMeta(Meta::M_REF_COUNT, $this->label, 1);
            } else {
                $parent_scope->setMeta(Meta::M_REF_COUNT, $this->label, $refcount + 1);
            }
        }
    }

    public function runTypeChecker()
    {
        // Pass
    }
}
