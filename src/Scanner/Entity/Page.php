<?php
namespace Scanner\Entity;

use Goutte\Client;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Scanner\Collection\PagesCollection;
use Scanner\Collection\FormsCollection;

class Page
{
	/** @var Client */
	private $client;
	private $uri;

	/** @var Crawler */
	private $crawler;

	public function __construct($uri)
	{
		$this->uri = $uri;
	}

	public function setClient(Client $client)
	{
		$this->client = $client;
	}

	public function getCrawler()
	{
		if(!isset($this->crawler)) {
			$this->crawler = $this->client->request('GET', $this->getUri());
		}
		return $this->crawler;
	}

	/**
	 * @return PagesCollection New Collection with all found links
	 */
	public function findLinkedPages()
	{
		$pages = new PagesCollection;

		$crawler = $this->getCrawler();
		foreach($crawler->filter('a') as $node)
		{
			$link = new Link($node, $this->uri);
			$page = new Page($link->getUri());
			$page->setClient($this->client);
			$pages->add($page);
		}

		return $pages;
	}

	/**
	 * Turn a DOMElement into a Symfony\Component\DomCrawler\Form
	 */
	private function elementToForm(\DOMElement $node)
	{
		$formname = sprintf('//form[@name="%s"]', $node->getAttribute('name'));
		// Find the submit field, or fallback to other submittables
		$crawler = $this->getCrawler()->filterXPath(sprintf(
			'%s//input[@type="submit"] | %s//button | %s//input[@type="button"] | %s//input[@type="image"]',
			$formname, $formname, $formname, $formname)
		);
		if(count($crawler)) {
			$form = $crawler->form();
		}
		else
		{
			// no submit buttons where found, add one ourselves
			$submit  = $node->ownerDocument->createElement('input');
			$submit->setAttribute('type', 'submit');
			$node->appendChild($submit);
			$form = new Form($submit, $this->getUri(), 'post');
		}
		return ($form);
	}

	/** @return FormsCollection */
	public function getForms()
	{

		$forms = new FormsCollection;
		foreach($this->getCrawler()->filterXPath('//form') as $node)
		{
			if($form = $this->elementToForm($node)) {
				$forms->add($form);
			}
		}
		return $forms;
	}

	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Removes the part after the #
	 */
	public function dropFragment()
	{
		list($this->uri) = explode('#', $this->uri);
	}
}