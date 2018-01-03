<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

class JsonDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'hyper' => null
            ),
            array(
                'case'  => 'Hypermedia flag not set',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => false
            ),
            array(
                'case'  => 'Hypermedia flag set to false',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => true
            ),
            array(
                'case'  => 'Hypermedia flag set to true',
                'hyper' => true
            )
        );

        return $data;
    }

    public static function getTestDisplay()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => true
                ),
                'task' => 'nothere'
            ),
            array(
                'case'      => 'Task with no before/after hooks',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => true
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => false,
                    'after'  => true
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks - before returns false',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => false
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks - after returns false',
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTestDisplayBrowse()
    {
        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => false,
                'hyper'    => false,
                'item'     => false,
                'limitstart' => 0,
                'limit' => 0,
            ),
            array(
                'case'   => 'Item not loaded, no hyperlinks, no callback',
                'output' => '[{"fakeapp_parent_id":"1","description":"First parent row"},{"fakeapp_parent_id":"2","description":"Second parent row"},{"fakeapp_parent_id":"3","description":"Parent with no children"}]'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => false,
                'item'     => true,
                'limitstart' => 1,
                'limit'      => 2,
            ),
            array(
                'case'   => 'Item loaded, no hyperlinks, no callback',
                'output' => '[{"fakeapp_parent_id":"2","description":"Second parent row"},{"fakeapp_parent_id":"3","description":"Parent with no children"}]'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => true,
                'item'     => true,
                'limitstart' => 0,
                'limit'      => 0,
            ),
            array(
                'case'   => 'Item loaded, with hyperlinks, no callback',
                'output' => '{"_links":{"self":{"href":"http:\/\/www.example.com\/"}},"_list":[{"fakeapp_parent_id":"1","description":"First parent row"},{"fakeapp_parent_id":"2","description":"Second parent row"},{"fakeapp_parent_id":"3","description":"Parent with no children"}]}'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => true,
                'item'     => true,
                'limitstart' => 0,
                'limit'      => 2,
            ),
            array(
                'case'   => 'Item loaded, with hyperlinks and pagination, no callback',
                'output' => '{"_links":{"self":{"href":"http:\/\/www.example.com\/"},"first":{"href":"http:\/\/www.example.com\/index.php?limitstart=0"},"next":{"href":"http:\/\/www.example.com\/index.php?limitstart=2"},"last":{"href":"http:\/\/www.example.com\/index.php?limitstart=2"}},"_list":[{"fakeapp_parent_id":"1","description":"First parent row"},{"fakeapp_parent_id":"2","description":"Second parent row"}]}'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => true,
                'item'     => true,
                'limitstart' => 2,
                'limit'      => 2,
            ),
            array(
                'case'   => 'Item loaded, with hyperlinks and pagination, no callback',
                'output' => '{"_links":{"self":{"href":"http:\/\/www.example.com\/"},"first":{"href":"http:\/\/www.example.com\/index.php?limitstart=0"},"prev":{"href":"http:\/\/www.example.com\/index.php?limitstart=0"},"last":{"href":"http:\/\/www.example.com\/index.php?limitstart=2"}},"_list":{"fakeapp_parent_id":"3","description":"Parent with no children"}}'
            )
        );

        $data[] = array(
            array(
                'callback' => 'foobar',
                'loaded'   => true,
                'hyper'    => false,
                'item'     => true,
                'limitstart' => 0,
                'limit'      => 1,
            ),
            array(
                'case'   => 'Item loaded, no hyperlinks, with callback',
                'output' => 'foobar([{"fakeapp_parent_id":"1","description":"First parent row"}])'
            )
        );

        return $data;
    }

    public static function getTestDisplayRead()
    {
        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => false,
                'hyper'    => false,
                'item'     => false
            ),
            array(
                'case'   => 'Item not loaded, no hyperlinks, no callback',
                'output' => '{"fakeapp_parent_id":"2","description":"Second parent row"}'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => false,
                'item'     => true
            ),
            array(
                'case'   => 'Item loaded, no hyperlinks, no callback',
                'output' => '{"fakeapp_parent_id":"3","description":"Parent with no children"}'
            )
        );

        $data[] = array(
            array(
                'callback' => null,
                'loaded'   => true,
                'hyper'    => true,
                'item'     => true
            ),
            array(
                'case'   => 'Item loaded, with hyperlinks, no callback',
                'output' => '{"_links":{"self":{"href":"http:\/\/www.example.com\/"}},"fakeapp_parent_id":"3","description":"Parent with no children"}'
            )
        );

        $data[] = array(
            array(
                'callback' => 'foobar',
                'loaded'   => true,
                'hyper'    => false,
                'item'     => true
            ),
            array(
                'case'   => 'Item loaded, no hyperlinks, with callback',
                'output' => 'foobar({"fakeapp_parent_id":"3","description":"Parent with no children"})'
            )
        );

        return $data;
    }
}
