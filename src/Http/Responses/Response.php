<?php

/**
 * Copyright 2018 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Responses\ResponseInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use Psr\Http\Message\ResponseInterface as PsrResponse;

/**
 * Class Response
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Response implements ResponseInterface
{

    /**
     * @var PsrResponse
     */
    private $response;

    /**
     * @var DocumentInterface|null
     */
    private $document;

    /**
     * Response constructor.
     *
     * @param PsrResponse $response
     * @param DocumentInterface|null $document
     */
    public function __construct(PsrResponse $response, DocumentInterface $document = null)
    {
        $this->response = $response;
        $this->document = $document;
    }

    /**
     * @inheritdoc
     */
    public function getPsrResponse()
    {
        return $this->response;
    }

    /**
     * @inheritdoc
     */
    public function getDocument()
    {
        return $this->document;
    }
}
