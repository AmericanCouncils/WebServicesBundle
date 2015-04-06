<?php

namespace AC\WebServicesBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends ServiceException
{
    public function __construct(ConstraintViolationListInterface $errors, $message = null)
    {
        $data = [];

        // assemble all
        foreach ($errors as $error) {
            $path = $error->getPropertyPath();

            if (!isset($data[$path])) {
                $data[$path] = [
                    'path' => $path,
                    'messages' => []
                ];
            }

            $data[$path]['messages'][] = $error->getMessage();
        }

        parent::__construct(422, ['errors' => array_values($data)], $message);
    }
}
