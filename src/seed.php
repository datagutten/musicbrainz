<?php


namespace datagutten\musicbrainz;


use DOMDocumentCustom;
use DOMElement;

class seed
{
    /**
     * @var DOMDocumentCustom
     */
    public DOMDocumentCustom $dom;

    /**
     * @var DOMElement
     */
    public DOMElement $fieldset;
    public DOMElement $form;
    public DOMElement $body;

    function __construct()
    {
        $this->dom = new DOMDocumentCustom;
        $this->dom->formatOutput = true;
    }

    public function build_page()
    {
        $this->body = $this->dom->createElement('body');
        $this->form = $this->dom->createElement_simple('form', $this->body, array('method' => 'POST', 'action' => 'https://musicbrainz.org/release/add'));
		$this->fieldset=$this->dom->createElement_simple('fieldset',$this->form);
		$this->dom->createElement_simple('input',$this->form,array('type'=>'submit','value'=>'seed'));
		$this->dom->createElement_simple('legend',$this->fieldset, null, 'Release information');
	}
	public function fieldset($legend)
	{
		$this->fieldset=$this->dom->createElement_simple('fieldset',$this->form);
		$this->dom->createElement_simple('legend',$this->fieldset, null, $legend);
	}

	public function show_page()
	{
		return $this->dom->saveXML($this->body);
	}

	/**
	 * @param $field
	 * @param $value
	 */
	function field($field,$value)
	{
		if(!is_object($this->fieldset) || $this->fieldset->tagName!='fieldset')
			die('Invalid fieldset');

		$this->dom->createElement_simple('label',$this->fieldset,array('for'=>$field),$field);
		$this->dom->createElement_simple('input',$this->fieldset,array('type'=>'text','value'=>$value,'name'=>$field,'id'=>$field));
		$this->dom->createElement_simple('br',$this->fieldset);
	}
	function message($message)
	{
		$this->dom->createElement_simple('p', $this->fieldset, null, $message);
	}
}