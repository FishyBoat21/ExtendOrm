<?php
namespace FishyBoat21\ExtendOrm;

/**
 * Cached, per-model description of the mapping declared through attributes.
 *
 * Built exactly once per model class by Model::Initialize() and then reused
 * for every later query.
 */
class ModelMap{
    public string $PrimaryKey = "";
    public string $Table = "";
    /** @var array<string,string> database column => PHP property */
    public array $FieldPropMap = array();
    /** @var array<string,string> PHP property => database column */
    public array $PropColumnMap = array();
    /** @var array<string,array<string,mixed>> PHP property => relation definition */
    public array $RelationMap = array();

}
