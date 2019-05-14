<?php

namespace InfyOm\Generator\Common;

use Illuminate\Support\Str;

class GeneratorFieldRelation
{
    /** @var string */
    public $type;
    public $inputs;
    public $relationName;

    public static function parseRelation($relationInput)
    {
        $inputs = explode(',', $relationInput);

        $relation = new self();
        $relation->type = array_shift($inputs);
        $modelWithRelation = explode(':', array_shift($inputs)); //e.g ModelName:relationName
        if (count($modelWithRelation) == 2) {
            $relation->relationName = $modelWithRelation[1];
            unset($modelWithRelation[1]);
        }
        $relation->inputs = array_merge($modelWithRelation, $inputs);

        return $relation;
    }

    public function getRelationFunctionText()
    {
        $relationName = (!empty($this->relationName)) ? $this->relationName : Str::camel($this->inputs[0]);

        switch ($this->type) {
            case '1t1':
                $functionName = $relationName;
                $relation = 'hasOne';
                $relationClass = 'HasOne';
                break;
            case '1tm':
                $functionName = $relationName;
                $relation = 'hasMany';
                $relationClass = 'HasMany';
                break;
            case 'mt1':
                if (!empty($this->relationName)) {
                    $relationName = $this->relationName;
                } elseif (isset($this->inputs[1])) {
                    $relationName = Str::camel(str_replace('_id', '', strtolower($this->inputs[1])));
                }
                $functionName = $relationName;
                $relation = 'belongsTo';
                $relationClass = 'BelongsTo';
                break;
            case 'mtm':
                $functionName = $relationName;
                $relation = 'belongsToMany';
                $relationClass = 'BelongsToMany';
                break;
            case 'hmt':
                $functionName = $relationName;
                $relation = 'hasManyThrough';
                $relationClass = 'HasManyThrough';
                break;
            default:
                $functionName = '';
                $relation = '';
                $relationClass = '';
                break;
        }

        if (!empty($functionName) and !empty($relation)) {
            return $this->generateRelation($functionName, $relation, $relationClass);
        }

        return '';
    }

    private function generateRelation($functionName, $relation, $relationClass)
    {

        $inputs = $this->inputs;
        $modelName = array_shift($inputs);

        $template = get_template('model.relationship', 'laravel-generator');

        $template = str_replace('$RELATIONSHIP_CLASS$', $relationClass, $template);
        $template = str_replace('$FUNCTION_NAME$', $functionName, $template);
        $template = str_replace('$RELATION$', $relation, $template);
        $template = str_replace('$RELATION_MODEL_NAME$', $modelName, $template);

        if (count($inputs) > 0) {
            $inputFields = implode("', '", $inputs);
            $inputFields = ", '".$inputFields."'";
        } else {
            $inputFields = '';
        }

        $template = str_replace('$INPUT_FIELDS$', $inputFields, $template);

        return $template;
    }

}
