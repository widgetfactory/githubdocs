<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.githubdocs
 *
 * @copyright   Copyright (C) 2014 Ryan Demmer. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require __DIR__ . '/parsedown/Parsedown.php';

/**
 * Plug-in to enable loading github MD files into content (e.g. articles)
 * This uses the {githubdocs} syntax
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.githubdocs
 */
class PlgContentGithubdocs extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 */
	public function __construct(& $subject, $config) {
		if(JFactory::getApplication()->isAdmin()) {
			return;
		}
		
		parent::__construct($subject, $config);
	}
	
	/**
	 * Plugin that loads github md file data within content
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  mixed   true if there is an error. Void otherwise.
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer')
		{
			return true;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, 'githubdocs') === false && strpos($article->text, 'githubdocs') === false)
		{
			return true;
		}

		// Expression to search for (slug)
		$regex = '/{githubdocs\s(.*?)}/i';

		// Find all instances of plugin and put in $matches for githubdocs
		// $matches[0] is full pattern match, $matches[1] is the slug
		preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

		// No matches, skip this
		if ($matches)
		{
			foreach ($matches as $match)
			{
				$slug 	= trim($match[1]);
				$output = $this->_load($slug);

				// We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
				$article->text = preg_replace("|$match[0]|", addcslashes($output, '\\$'), $article->text, 1);
			}
		}
	}

	/**
	 * Loads data from github and renders
	 *
	 * @param   string  $slug  Article slug
	 *
	 * @return  mixed
	 */
	protected function _load($slug)
	{
		$url 	= 'https://raw.githubusercontent.com/' . $this->params->get('account') . '/' . $this->params->get('repo') . '/master/' . $slug . '.md';		
		$data 	= @file_get_contents($url);
		
		if ($data !== false) {
			return $this->parseMarkdown($data);
		}
		
		return false;
	}
	
	protected function parseMarkdown($data) 
	{
		$Parsedown = new Parsedown();
		return $Parsedown->text($data); 
	}
}
