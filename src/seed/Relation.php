<?php

namespace datagutten\musicbrainz\seed;

class Relation extends Element
{
    protected array $fields = ['direction', 'ended', 'end', 'type', 'begin'];
    protected array $field_aliases = [
        'type_id' => 'type-id',
        'target_type' => 'target-type',
        'target_credit' => 'target-credit'
    ];

    public string $id;
    /***
     * @var string Relation type ID
     */
    public string $type_id;
    /**
     * @var string Relation type
     */
    public string $type;
    /**
     * @var string Target type
     */
    public string $target_type;
    /**
     * @var Artist Target element
     */
    public Element $target;

    /**
     * @var URL
     */
    public URL $url;

    public function __construct($args)
    {
        $this->register_fields($args);
        if ($this->target_type == 'artist')
            $this->target = new Artist($args['artist']);
        elseif ($this->target_type == 'url')
        {
            $this->target = new URL([
                'id' => $args['url']['id'],
                'url' => $args['url']['resource'],
                'type' => $args['type'],
            ]);
        }
    }
}