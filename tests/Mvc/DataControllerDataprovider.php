<?php

class DataControllerDataprovider
{
    public static function getTestGetModel()
    {
        $data[] = array(
            array(
                'model' => 'datafoobars'
            ),
            array(
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'model' => null
            ),
            array(
                'exception' => true
            )
        );

        return $data;
    }
}