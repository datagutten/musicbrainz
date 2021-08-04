<?php

namespace datagutten\musicbrainz;

use ArrayAccess;

class SimpleArrayAccess implements ArrayAccess
{
    public function offsetExists($offset): bool
    {
        return !empty($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}