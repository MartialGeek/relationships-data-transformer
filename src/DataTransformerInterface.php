<?php
/**
 * Copyright (C) 2016  Martial Saunois
 *
 * Please read the LICENSE file at the root directory of the project for the full notice.
 */

namespace Martial\RelationshipsDataTransformer;

interface DataTransformerInterface
{
    /**
     * Takes the database results as first argument and an array of options as second argument.
     * Returns the transformed data as array.
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function transform(array $data, array $options);
}
