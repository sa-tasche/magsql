<?php

namespace SQLBuilder;

class Expression
{
    public $driver;

    /* child */
    public $child;

    /* parent expression */
    public $parent;

    /* op code connects to parent expression */
    public $parentOp;

    /* is group ? ( ) */
    public $isGroup;

    public $cond;

    public function setCond($cond)
    {
        if( $this->cond ) {
            return $this->and()->setCond($cond);
        }
        $this->cond = $cond;
        return $this;
    }

    public function is($c,$n)
    {
        return $this->setCond( array( $c , 'is' , array($n) ));
    }

    public function isNot($c,$n)
    {
        return $this->setCond( array( $c , 'is not' , array($n) ));
    }

    public function equal($c,$n)
    {
        return $this->setCond(array( $c , '=' , $n ));
    }

    public function notEqual($c,$n)
    {
        return $this->setCond(array( $c, '!=' , $n ));
    }

    public function like($c,$n)
    {
        return $this->setCond(array( $c, 'like', $n ));
    }


    public function __call($method,$args)
    {
        switch( $method )
        {
            case 'and':
                return $this->newAnd();
                break;
            case 'or':
                return $this->newOr();
                break;
        }
    }

    public function createExpr($op = 'AND')
    {
        $subexpr = new self;
        $subexpr->parent = $this;
        $subexpr->parentOp = $op;
        $subexpr->driver = $this->driver;
        $this->child = $subexpr;
        return $subexpr;
    }

    public function newAnd()
    {
        return $this->createExpr('AND');
    }

    public function newOr()
    {
        return $this->createExpr('OR');
    }

    public function group($op = 'AND')
    {
        $subexpr = $this->createExpr($op);
        $subexpr->isGroup = true;
        return $subexpr;
    }

    public function ungroup()
    {
        return $this->parent;
    }

    public function back()
    {
        if( $this->driver )
            return $this->driver;
        return $this->parent;
    }

    public function inflate()
    {
        $sql = '';

        if( $this->parent )
            $sql .= $this->parentOp . ' ';

        if( $this->isGroup )
            $sql .= '(';

        list($k,$op,$v) = $this->cond;
		if( $this->driver->placeholder ) {
            $sql .= $this->driver->getQuoteColumn($k) . ' ' . $op . ' '  . $this->driver->getPlaceHolder($k);

            /*
            if( is_array($v) ) {
                $sql .= $this->driver->getQuoteColumn($k) . ' ' . $op . ' ' . $v[0];
            } else {
                $sql .= $this->driver->getQuoteColumn( $k ) . ' ' . $op . ' '  . $this->getPlaceHolder($k);
            }
            */
		}
		else {
            if( is_array($v) ) {
                $sql .= $this->driver->getQuoteColumn($k) . ' ' . $op . ' ' . $v[0];
            } else {
                $sql .= $this->driver->getQuoteColumn($k) . ' ' . $op . ' ' 
                    . '\'' 
                    . $this->driver->escape($v)
                    . '\'';
            }
		}

        if( $this->child )
            $sql .= ' ' . $this->child->inflate();

        if( $this->isGroup )
            $sql .= ')';

        return $sql;
    }

    public function __toString()
    {

    }

}

