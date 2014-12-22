<?php

namespace Awf\Tests\Stubs\Mvc\DataModel\Filter;

use Awf\Mvc\DataModel\Filter\AbstractFilter;

class FilterStub extends AbstractFilter
{
    public function partial($value)
    {
        return '';
    }

    public function between($from, $to, $include = true)
    {
        return '';
    }

    public function outside($from, $to, $include = false)
    {
        return '';
    }

    public function interval($from, $interval)
    {
        return '';
    }

    protected function additionalSearchHidden()
    {

    }

    public function additionalSearchVisible()
    {

    }
}