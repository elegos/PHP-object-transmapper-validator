PHP object trans-mapper validator
===

[![codecov](https://codecov.io/gh/elegos/PHP-object-transmapper-validator/branch/master/graph/badge.svg)](https://codecov.io/gh/elegos/PHP-object-transmapper-validator)

This library aims to easily create both data-mappers and validators from the HTTP request down to a model.

Instead of manually validating every single request checking if the variable exists, if the type is correct etc,
and eventually push the data into a model, this library is able to parse the request and automatically map it into a
given class, as easy as the following:

    $requestData = json_decode($payload); // whatever
    $data = $this->transmapper->map($requestData, MyModel::class[, ...$override]);

It supports all the scalar values (`bool`, `int`, `float`, `string`), classes and arrays (both of scalar values or classes)
recursively.

How to use
---
First of all, create a new model class where the data will be pushed in. **Its constructor must be argument-less!**

    class MyModel {
        // eventual argument-less constructor
        public function __constructor() {
            ...
        }

Then we can add some variables, using the `GiacomoFurlan\ObjectTransmapperValidator\Annotation\Validation\Validate` annotation
in order to describe the validation

    class MyModel {
        /**
        * @var bool
        * @Validate(
        *     options here
        * )
        */
        private $myBoolean;
        
The only mandatory option is `type`, which can be any of the scalar values and their aliases (`bool`, `boolean`, `int`,
`integer`, `float`, `double`, `string`), an array of scalar values (es. `int[]` or `integer[]`), a class name
(fully qualified name, i.e. `My\Full\Namespace\ClassName`) or an array of objects (always fully qualified name,
i.e. `My\Full\Namespace\ClassName[]`).

You can optionally extend the model with `GiacomoFurlan\ObjectTransmapperValidator\Model\MappedModel`, which provides
the method `isMapped(string $attribute) : bool`. Note that extending this model will forbid the use of `_mapped` as
attribute name in your model (the attribute's value will be ignored).

To trans-map the standard object to the given model, call the transmapper's map method:

    $myTransmapper->map($stdClass, MyModel::class[, $override1, $override2...])

Overrides allow you to dynamically change some aspects of the validation in order to make the system dynamic depending
on the environment variables, in particular: `mandatory`, `nullable` and `regex`. These overrides will follow the specs
of the options written below. The format is the following: `['dot.notation.variable' => ['mandatory' => true, 'nullable' => true]]`

Override examples:

- `['myString' => ['regex' => '/^must_start_with_string/']]`
- `['myInt' => ['mandatory' => false]]`
- `['mySubModel.myVar' => [...]]`

The options are:

- `type` string
- `mandatory` boolean (default true), the source object must contain the attribute
- `nullable` boolean (default false), the source may contain the attribute, even if it can be null regardless of the specified type
- `regex` string (default null), the regex to check against the value to map. Performed only if `type="string"`
- `typeExceptionClass` string, the fully-qualified class name of the exception to throw in case of type mismatch
- `typeExceptionMessage` string, the message thrown for the previous exception, must instert two `%s`: (1) found type, (2) expected type
- `typeExceptionCode` int, the type mismatch exception's code (default 3000)
- `mandatoryExceptionClass` string, the fully-qualified class name of the exception to throw in case of missing mandatory attribute
- `mandatoryExceptionMessage` string, the message thrown for the previous exception, must instert one `%s` for the missing attribute's name
- `mandatoryExceptionCode` int, the mandatory exception's code (default 3001)
- `regexExceptionClass` string, the fully-qualified class name of the exception to throw in case of regex mismatch
- `regexExceptionMessage` string, the message thrown for the previous exception, must instert two `%s`: (1) the value, (2) the regex
- `regexExceptionCode` int, the regex exception's code (default 3002)
