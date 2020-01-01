<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

use Awf\Document\Document;
use Awf\Uri\Uri;

$headers = $this->getHTTPHeaders();

if (!empty($headers)) foreach ($headers as $header => $value)
{
	JFactory::getApplication()->setHeader($header, $value, true);
}

$scripts = $this->getScripts();
$scriptDeclarations = $this->getScriptDeclarations();
$styles = $this->getStyles();
$styleDeclarations = $this->getStyleDeclarations();

// Scripts before the template ones
if(!empty($scripts)) foreach($scripts as $url => $params)
{
	if($params['before'])
	{
		JFactory::getApplication()->getDocument()->addScript($url, $params['mime']);
	}
}

// CSS files before the template CSS
if(!empty($styles)) foreach($styles as $url => $params)
{
	if($params['before'])
	{
		$media = ($params['media']) ? "media=\"{$params['media']}\"" : '';
		JFactory::getApplication()->getDocument()->addStyleSheet($url, $params['mime'], $media);
	}
}

// Load jQuery and Bootstrap
JHtml::_('behavior.framework', true);
JHtml::_('bootstrap.framework');

// Scripts after the template ones
if(!empty($scripts)) foreach($scripts as $url => $params)
{
	if(!$params['before'])
	{
		JFactory::getApplication()->getDocument()->addScript($url, $params['mime']);
	}
}

// Script declarations
if (!is_array($scriptDeclarations))
{
	$scriptDeclarations = array();
}

$scriptDeclarations[] = 'window.addEventListener(\'DOMContentLoaded\', function(event) { akeeba.fef.menuButton(); akeeba.fef.tabs(); });';

foreach ($scriptDeclarations as $type => $content)
{
	if ($this->getContainer()->input->get('tmpl') == 'component')
	{
		JFactory::getApplication()->getDocument()->addScriptDeclaration($content, $type);
	}
	else
	{
		echo <<< HTML
<script type="$type">
akeeba.loadScripts[akeeba.loadScripts.length] = function () {
	$content
}
</script>
HTML;
	}
}

// CSS declarations after the template CSS
if(!empty($styles)) foreach($styles as $url => $params)
{
	if(!$params['before'])
	{
		$media = ($params['media']) ? "media=\"{$params['media']}\"" : '';
		JFactory::getApplication()->getDocument()->addStyleSheet($url, $params['mime'], $media);
	}
}

// Script declarations
if(!empty($styleDeclarations)) foreach($styleDeclarations as $type => $content)
{
	JFactory::getApplication()->getDocument()->addScriptDeclaration($content, $type);
}
