<?php

namespace Imi\Model\Annotation\Relation;

use Imi\Bean\Annotation\Parser;

/**
 * 用于多态一对一、一对多关联被关联的模型中使用，查询对应的左侧模型.
 *
 * @Annotation
 * @Target("PROPERTY")
 * @Parser("Imi\Model\Parser\RelationParser")
 */
class PolymorphicToOne extends RelationBase
{
    /**
     * 只传一个参数时的参数名.
     *
     * @var string
     */
    protected $defaultFieldName = 'model';

    /**
     * 关联的模型类
     * 可以是包含命名空间的完整类名
     * 可以同命名空间下的类名.
     *
     * @var string
     */
    public $model;

    /**
     * 关联的模型用于关联的字段.
     *
     * @var string
     */
    public $modelField;

    /**
     * 当前模型用于关联的字段.
     *
     * @var string
     */
    public $field;

    /**
     * 多态类型字段名.
     *
     * @var string
     */
    public $type;

    /**
     * 多态类型字段值
     *
     * @var mixed
     */
    public $typeValue;

    /**
     * 为查询出来的模型指定字段.
     *
     * @var string[]|null
     */
    public $fields = null;
}
