<?php

class TestInstance {

}

/**
 * Trying to create instance will throw error if typeless param is not specified
 */
class TestInstanceTypelessParam {
  public function __construct(int $typelessParam)
  {
  }
}

class InjectorTestNullableParams
{
  public ?TestInstance $string;

  public function __construct(?TestInstance $instance = null)
  {
    $this->instance = $instance;
  }
}


class InjectorTestNullableParamTypelessDependency
{
  public function __construct(
    public ?TestInstanceTypelessParam $optional = null
  ) {
  }
}