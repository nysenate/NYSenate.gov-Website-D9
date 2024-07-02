<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: opentelemetry/proto/trace/v1/trace.proto

namespace Opentelemetry\Proto\Trace\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A collection of Spans produced by an InstrumentationScope.
 *
 * Generated from protobuf message <code>opentelemetry.proto.trace.v1.ScopeSpans</code>
 */
class ScopeSpans extends \Google\Protobuf\Internal\Message
{
    /**
     * The instrumentation scope information for the spans in this message.
     * Semantically when InstrumentationScope isn't set, it is equivalent with
     * an empty instrumentation scope name (unknown).
     *
     * Generated from protobuf field <code>.opentelemetry.proto.common.v1.InstrumentationScope scope = 1;</code>
     */
    protected $scope = null;
    /**
     * A list of Spans that originate from an instrumentation scope.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.trace.v1.Span spans = 2;</code>
     */
    private $spans;
    /**
     * The Schema URL, if known. This is the identifier of the Schema that the span data
     * is recorded in. To learn more about Schema URL see
     * https://opentelemetry.io/docs/specs/otel/schemas/#schema-url
     * This schema_url applies to all spans and span events in the "spans" field.
     *
     * Generated from protobuf field <code>string schema_url = 3;</code>
     */
    protected $schema_url = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Opentelemetry\Proto\Common\V1\InstrumentationScope $scope
     *           The instrumentation scope information for the spans in this message.
     *           Semantically when InstrumentationScope isn't set, it is equivalent with
     *           an empty instrumentation scope name (unknown).
     *     @type \Opentelemetry\Proto\Trace\V1\Span[]|\Google\Protobuf\Internal\RepeatedField $spans
     *           A list of Spans that originate from an instrumentation scope.
     *     @type string $schema_url
     *           The Schema URL, if known. This is the identifier of the Schema that the span data
     *           is recorded in. To learn more about Schema URL see
     *           https://opentelemetry.io/docs/specs/otel/schemas/#schema-url
     *           This schema_url applies to all spans and span events in the "spans" field.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Trace\V1\Trace::initOnce();
        parent::__construct($data);
    }

    /**
     * The instrumentation scope information for the spans in this message.
     * Semantically when InstrumentationScope isn't set, it is equivalent with
     * an empty instrumentation scope name (unknown).
     *
     * Generated from protobuf field <code>.opentelemetry.proto.common.v1.InstrumentationScope scope = 1;</code>
     * @return \Opentelemetry\Proto\Common\V1\InstrumentationScope|null
     */
    public function getScope()
    {
        return $this->scope;
    }

    public function hasScope()
    {
        return isset($this->scope);
    }

    public function clearScope()
    {
        unset($this->scope);
    }

    /**
     * The instrumentation scope information for the spans in this message.
     * Semantically when InstrumentationScope isn't set, it is equivalent with
     * an empty instrumentation scope name (unknown).
     *
     * Generated from protobuf field <code>.opentelemetry.proto.common.v1.InstrumentationScope scope = 1;</code>
     * @param \Opentelemetry\Proto\Common\V1\InstrumentationScope $var
     * @return $this
     */
    public function setScope($var)
    {
        GPBUtil::checkMessage($var, \Opentelemetry\Proto\Common\V1\InstrumentationScope::class);
        $this->scope = $var;

        return $this;
    }

    /**
     * A list of Spans that originate from an instrumentation scope.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.trace.v1.Span spans = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getSpans()
    {
        return $this->spans;
    }

    /**
     * A list of Spans that originate from an instrumentation scope.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.trace.v1.Span spans = 2;</code>
     * @param \Opentelemetry\Proto\Trace\V1\Span[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setSpans($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Opentelemetry\Proto\Trace\V1\Span::class);
        $this->spans = $arr;

        return $this;
    }

    /**
     * The Schema URL, if known. This is the identifier of the Schema that the span data
     * is recorded in. To learn more about Schema URL see
     * https://opentelemetry.io/docs/specs/otel/schemas/#schema-url
     * This schema_url applies to all spans and span events in the "spans" field.
     *
     * Generated from protobuf field <code>string schema_url = 3;</code>
     * @return string
     */
    public function getSchemaUrl()
    {
        return $this->schema_url;
    }

    /**
     * The Schema URL, if known. This is the identifier of the Schema that the span data
     * is recorded in. To learn more about Schema URL see
     * https://opentelemetry.io/docs/specs/otel/schemas/#schema-url
     * This schema_url applies to all spans and span events in the "spans" field.
     *
     * Generated from protobuf field <code>string schema_url = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setSchemaUrl($var)
    {
        GPBUtil::checkString($var, True);
        $this->schema_url = $var;

        return $this;
    }

}
