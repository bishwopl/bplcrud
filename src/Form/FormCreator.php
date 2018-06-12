<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Form;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Form\Form;
use Zend\Form\Fieldset;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Reads annotation and creates form and filter
 */
class FormCreator {

    protected $objectClassName;
    protected $classNameOnly;
    protected $hyadratorClassName;
    protected $formNamespace;
    protected $saveDestination;
    protected $defaultElementCSSClass;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;
    protected $fieldSetData = [];

    public function __construct(
        ClassMetadata $classMetadata, $hyadratorClassName, $formNamespace, $saveDestination, $defaultElementCSSClass = ""
    ) {
        $this->objectClassName = $classMetadata->name;
        $this->hyadratorClassName = $hyadratorClassName;
        $this->formNamespace = $formNamespace;
        $this->saveDestination = $saveDestination;
        $this->classNameOnly = $this->getClassNameOnly($this->objectClassName);
        $this->classMetadata = $classMetadata;
        $this->defaultElemectCSSClass = $defaultElementCSSClass;

        if (!is_dir($this->saveDestination . '/' . $this->classNameOnly)) {
            mkdir($this->saveDestination . '/' . $this->classNameOnly);
        }

        $this->saveDestination .= '/' . $this->classNameOnly;
        $this->formNamespace .= '\\' . $this->classNameOnly;
    }

    public function generateForm() {
        $this->createFieldSet();
        $this->createForm();
    }

    private function createFieldSet() {
        $fieldsetGenerator = new ClassGenerator(
            $this->getClassNameOnly($this->objectClassName) . 'Fieldset', $this->formNamespace, null, Fieldset::class, [InputFilterProviderInterface::class], [], [], null
        );

        $fieldMappings = $this->classMetadata->fieldMappings;
        $associationMapping = $this->classMetadata->associationMappings;

        $body = 'parent::__construct($name, $options);' . PHP_EOL . PHP_EOL
            . '$this->setHydrator(new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($persistanceManager));' . PHP_EOL
            . '$this->setObject(new \\' . $this->objectClassName . '());' . PHP_EOL . PHP_EOL;

        $bodyFilter = "return [" . PHP_EOL;

        foreach ($fieldMappings as $field) {

            $fieldName = $field['fieldName'];
            $type = $field['type'];
            $isPrimary = isset($field['id']) && $field['id'] == true;
            $body .= '$this->add([' . PHP_EOL
                . '"type" => ' . $this->getElementType($type, $isPrimary) . '::class, ' . PHP_EOL
                . '"name" => "' . $fieldName . '", ' . PHP_EOL
                . '"options" => [' . PHP_EOL
                . '"label" => "' . $fieldName . '",' . PHP_EOL . '], ' . PHP_EOL
                . '"attributes" => [' . PHP_EOL
                . '"id"   => "' . $fieldName . '_id", ' . PHP_EOL
                . '"placeholder"=>"' . $fieldName . '",' . PHP_EOL
                . '"class" => "' . $this->defaultElemectCSSClass . '",' . PHP_EOL
                . '],' . PHP_EOL
                . ']);' . PHP_EOL;
            $bodyFilter .= $this->createFilter($field);
        }

        foreach ($associationMapping as $field) {
            $fieldName = $field['fieldName'];
            $targetEntity = $field['targetEntity'];

            if (isset($field['joinColumns'])) {
                /* a select box is required */
                $body .= '$this->add([' . PHP_EOL
                    . '"name" => "' . $fieldName . '",' . PHP_EOL
                    . '"type" => \DoctrineModule\Form\Element\ObjectSelect::class, ' . PHP_EOL
                    . '"options" => [' . PHP_EOL
                    . '"label" => "' . $fieldName . '",' . PHP_EOL
                    . '"object_manager" => $persistanceManager,' . PHP_EOL
                    . '"target_class" => \\' . $targetEntity . '::class, ' . PHP_EOL
                    . '"property" => "[You need to provide name of property to display]", ' . PHP_EOL
                    . '"display_empty_item" => true,' . PHP_EOL
                    . '"empty_item_label"   => "Select ' . $fieldName . '",' . PHP_EOL
                    . '],' . PHP_EOL
                    . '"attributes" => [ ' . PHP_EOL
                    . '"class" => "' . $this->defaultElemectCSSClass . '",' . PHP_EOL
                    . '"id" => "' . $fieldName . '_id",' . PHP_EOL
                    . '"placeholder"=>"' . $fieldName . '",' . PHP_EOL
                    . ']' . PHP_EOL
                    . ']);' . PHP_EOL;
                $bodyFilter .= $this->createFilter($field);
            } else {
                /* otherwise a full fieldset is required 
                 * So fieldsets for target entities need to be created first
                 * This method assumes that
                 */
                $this->fieldSetData[] = $fieldName;
                $fieldsetClassName = $this->getClassNameOnly($targetEntity);
                $fieldSetClass = '\\' . str_replace($this->classNameOnly, '', $this->formNamespace) . $fieldsetClassName . '\\' . $fieldsetClassName . 'Fieldset';
                $body .= '$this->add([ ' . PHP_EOL
                    . '"type" => \Zend\Form\Element\Collection::class, ' . PHP_EOL
                    . '"name" => "' . $fieldName . '", ' . PHP_EOL
                    . '"options" => [' . PHP_EOL
                    . '"label" => "Multiple ' . $fieldName . '",' . PHP_EOL
                    . '"count" => $noOf' . ucfirst($fieldName) . 'Fieldsets,' . PHP_EOL
                    . '"should_create_template" => true,' . PHP_EOL
                    . '"allow_add" => true,' . PHP_EOL
                    . '"target_element" => new ' . $fieldSetClass . '(' . $this->getConstructParamsInString() . ') ,' . PHP_EOL
                    . '],' . PHP_EOL
                    . ']);' . PHP_EOL;
            }
        }
        $bodyFilter .= "];" . PHP_EOL;

        $method = new MethodGenerator(
            "__construct", $this->getConstructParams(), MethodGenerator::FLAG_PUBLIC, $body
        );

        $methodFilter = new MethodGenerator(
            "getInputFilterSpecification", [], MethodGenerator::FLAG_PUBLIC, $bodyFilter
        );

        $fieldsetGenerator->addMethodFromGenerator($method);
        $fieldsetGenerator->addMethodFromGenerator($methodFilter);
        $this->saveClass($fieldsetGenerator);
    }

    private function createForm() {
        $baseFieldsetName = '$' . lcfirst($this->classNameOnly) . 'Fieldset';
        $body = 'parent::__construct($name, $options);' . PHP_EOL . PHP_EOL
            . $baseFieldsetName . ' = new \\' . $this->formNamespace . '\\' . $this->classNameOnly . 'Fieldset(' . $this->getConstructParamsInString() . ');' . PHP_EOL
            . $baseFieldsetName . '->setUseAsBaseFieldset(true);' . PHP_EOL
            . '$this->add(' . $baseFieldsetName . ');' . PHP_EOL;

        $body .= '$this->setAttribute("enctype", "multipart/form-data"); ' . PHP_EOL
            . '$this->setAttribute("METHOD", "POST"); ' . PHP_EOL
            . '$this->setAttribute("class", "form-horizontal"); ' . PHP_EOL . PHP_EOL
            . '$this->add([' . PHP_EOL
            . '"name" => "submit",' . PHP_EOL
            . '"attributes" => [' . PHP_EOL
            . '"type"  => "submit",' . PHP_EOL
            . '"value" => "Submit",' . PHP_EOL
            . '"id"=> "presonalInfoSubmitButton",' . PHP_EOL
            . '"class" => "btn btn-primary" ' . PHP_EOL
            . '],' . PHP_EOL
            . ']);' . PHP_EOL;
        $method = new MethodGenerator(
            "__construct", $this->getConstructParams(), MethodGenerator::FLAG_PUBLIC, $body
        );

        $formGenerator = new ClassGenerator(
            $this->classNameOnly . 'Form', $this->formNamespace, null, Form::class, [], [], [], null
        );
        $formGenerator->addMethodFromGenerator($method);
        $this->saveClass($formGenerator);
    }

    private function getConstructParams() {
        $constructParams = [['name' => 'persistanceManager', 'type' => ObjectManager::class], 'name=NULL', 'options=[]'];
        $fieldsetCounts = [];
        foreach ($this->fieldSetData as $f) {
            $fieldsetCounts[] = 'noOf' . ucfirst($f) . 'Fieldsets=1';
        }
        return array_merge_recursive($constructParams, $fieldsetCounts);
    }

    private function getConstructParamsInString() {
        $constructParams = ['$persistanceManager', '$name', '$options'];
        foreach ($this->fieldSetData as $f) {
            $constructParams[] = '$noOf' . ucfirst($f) . 'Fieldsets';
        }
        return implode(',', $constructParams);
    }

    private function createFilter($fieldDetail) {
        $fieldName = $fieldDetail['fieldName'];
        $required = 'false';
        $isPrimary = isset($fieldDetail['id']) && $fieldDetail['id'] == true;
        if($isPrimary){
            $required = 'false';
        }
        elseif (isset($fieldDetail['nullable'])) {
            $required = $fieldDetail['nullable'] == false ? 'true' : 'false';
        }

        return '"' . $fieldName . '" => [' . PHP_EOL
            . '"required"=>' . $required . ', ' . PHP_EOL
            . '"filters" => [],' . PHP_EOL
            . '"validators" => [],' . PHP_EOL . '],' . PHP_EOL;
    }

    private function saveClass(ClassGenerator $cg) {
        $contents = $cg->generate();
        file_put_contents($this->saveDestination . '/' . $cg->getName() . '.php', "<?php\n" . $contents);
    }

    private function getClassNameOnly($name) {
        $temp = explode('\\', $name);
        return $temp[sizeof($temp) - 1];
    }

    private function getElementType($data, $isPrimary = false) {
        $type = \Zend\Form\Element\Text::class;
        if ($isPrimary) {
            $type = \Zend\Form\Element\Hidden::class;
        } elseif ($data == 'string') {
            $type = \Zend\Form\Element\Text::class;
        } elseif ($data == 'text') {
            $type = \Zend\Form\Element\Textarea::class;
        } elseif ($data == 'date') {
            $type = \Zend\Form\Element\Date::class;
        } elseif ($data == 'datetime') {
            $type = \Zend\Form\Element\DateTime::class;
        } elseif ($data == 'time') {
            $type = \Zend\Form\Element\Time::class;
        } elseif ($data == 'timestamp') {
            $type = \Zend\Form\Element\Time::class;
        } elseif ($data == 'blob') {
            $type = \Zend\Form\Element\File::class;
        }
        return '\\' . $type;
    }

}
