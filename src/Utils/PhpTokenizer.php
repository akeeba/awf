<?php
/**
 * @package     Awf
 * @copyright   2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 3 or later
 */
namespace Awf\Utils;

/**
 * Parses an PHP string and extract the requests tokens from it
 *
 * @package Awf\Utils
 */
class PhpTokenizer
{
    /** @var  string    PHP code that will be analyzed */
    private $code;

    /**
     * Class constructor
     *
     * @param   string  $code   PHP code that will be analyzed
     */
    public function __construct($code = null)
    {
        $this->code = $code;
    }

    /**
     * Sets the code that will be analyzed
     *
     * @param   string  $code   PHP code that will be analyzed
     *
     * @return  $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function searchToken($type, $name, $skip = 0)
    {
        if(!$this->code)
        {
            throw new \RuntimeException('Please set some code before trying to analyze it');
        }

        $startLine  = $this->findToken($type, $name, $skip);

        // Token not found? Let's raise an exception
        if(!$startLine)
        {
            throw new \RuntimeException('Token '.$type.' with value '.$name.' not found');
        }

        $endLine = $this->findToken('AWF_SEMICOLON', ';', $startLine);
        $data    = $this->extractData($startLine, $endLine);

        return array(
            'endLine' => $endLine,
            'data'    => $data
        );
    }

    protected function findToken($type, $name, $skip = 0)
    {
        $tokens = token_get_all($this->code);

        $iterator   = new Collection($tokens);
        $collection = $iterator->getCachingIterator();
        $ignoreSkip = false;

        // Ok let's start looking for the requested token
        foreach($collection as $token)
        {
            $line = 0;

            if(is_string($token))
            {
                $info['token'] = $this->tokenChar($token);
                $info['value'] = $token;
            }
            else
            {
                $info['token'] = token_name($token[0]);
                $info['value'] = $token[1];
                $line          = $token[2];
            }

            // If I have the skip argument I have to skip the first lines (literal chars are always skipped since they
            // don't report the line they're in)
            if($skip && ($line < $skip) && !$ignoreSkip)
            {
                continue;
            }

            // Ok, now I can stop checking for the skip (again, literal chars have no line info, otherwise I'll
            // keep skipping them all the time
            $ignoreSkip = true;

            // Ok token found, let's get the line
            if($info['token'] == $type && $info['value'] == $name)
            {
                // If it's an array, that's easy
                if(is_array($token))
                {
                    return $token[2];
                }
                else
                {
                    // It's a string, I have to fetch the next token so I'll have the proper line number
                    // To be sure, I have to iterate until I finally get an array for the token
                    $next = null;

                    while(!is_array($next))
                    {
                        $next = $collection->getInnerIterator()->current();

                        // The next token is not an array (ie it's a char like ;.=?)? Move the iterator forward and fetch
                        // the next token
                        if(!is_array($next))
                        {
                            $collection->getInnerIterator()->next();
                            continue;
                        }

                        return $next[2];
                    }
                }
            }
        }

        return null;
    }

    protected function extractData($start, $end)
    {
        $result = '';
        $lines  = explode("\n", $this->code);

        if(!isset($lines[$start]))
        {
            return $result;
        }

        for($i = ($start - 1); $i < $end; $i++)
        {
            if(!isset($lines[$i]))
            {
                break;
            }

            $result .= $lines[$i]."\n";
        }

        return $result;
    }

    /**
     * PHP doesn't have a token for single chars as (),;= and so on.
     * This function sets some custom tokens for consistency
     *
     * @param   string  $char
     *
     * @return  string  Our custom token
     */
    private function tokenChar($char)
    {
        switch($char)
        {
            case ';':
                return 'AWF_SEMICOLON';
        }

        return 'AWF_UNKNOWN';
    }
}