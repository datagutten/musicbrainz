<?php


namespace datagutten\musicbrainz\seed;


class Label extends Element
{
    public static string $entity = 'label';
    public string $mbid;
    /**
     * @var string Label name
     */
    public string $name;
    /**
     * @var string Alias for name
     * @see self::$name
     */
    public string $title;
    public string $catalog_number;
    protected array $fields = ['id', 'catalog_number', 'name'];

    /**
     * @var Release[] Label releases
     */
    public array $releases;

    public function __construct(array $args)
    {
        $this->register_fields($args);
        if (!empty($args['releases']))
        {
            foreach ($args['releases'] as $release)
            {
                $release_obj = new Release($release);
                $release_obj->labels[] = $this;
                $this->releases[] = $release_obj;
            }
        }
    }
}