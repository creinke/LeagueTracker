<?php
namespace App\Form\DataTransformer;

use App\Form\Type\BooleanType;
use Symfony\Component\Form\DataTransformerInterface;

class BooleanTypeToBooleanTransformer implements DataTransformerInterface {

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value): int {
        if (true === $value || BooleanType::VALUE_TRUE === (int) $value) {
            return BooleanType::VALUE_TRUE;
        }
        return BooleanType::VALUE_FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): bool {
        if (BooleanType::VALUE_TRUE === (int) $value) {
            return true;
        }
        return false;
    }
}