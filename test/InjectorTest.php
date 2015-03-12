<?php

namespace Auryn\Test;

use Auryn\Injector;

class InjectorTest extends \PHPUnit_Framework_TestCase {

    public function testMakeInstanceInjectsSimpleConcreteDependency() {
        $injector = new Injector;
        $this->assertEquals(new TestNeedsDep(new TestDependency),
            $injector->make('Auryn\Test\TestNeedsDep')
        );
    }

    public function testMakeInstanceReturnsNewInstanceIfClassHasNoConstructor() {
        $injector = new Injector;
        $this->assertEquals(new TestNoConstructor, $injector->make('Auryn\Test\TestNoConstructor'));
    }

    public function testMakeInstanceReturnsAliasInstanceOnNonConcreteTypehint() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
        $this->assertEquals(new DepImplementation, $injector->make('Auryn\Test\DepInterface'));
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnInterfaceWithoutAlias() {
        $injector = new Injector;
        $injector->make('Auryn\Test\DepInterface');
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnNonConcreteCtorParamWithoutImplementation() {
        $injector = new Injector;
        $injector->make('Auryn\Test\RequiresInterface');
    }

    public function testMakeInstanceBuildsNonConcreteCtorParamWithAlias() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
        $obj = $injector->make('Auryn\Test\RequiresInterface');
        $this->assertInstanceOf('Auryn\Test\RequiresInterface', $obj);
    }

    public function testMakeInstancePassesNullCtorParameterIfNoTypehintOrDefaultCanBeDetermined() {
        $injector = new Injector;
        $nullCtorParamObj = $injector->make('Auryn\Test\ProvTestNoDefinitionNullDefaultClass');
        $this->assertEquals(new ProvTestNoDefinitionNullDefaultClass, $nullCtorParamObj);
        $this->assertEquals(NULL, $nullCtorParamObj->arg);
    }

    public function testMakeInstanceReturnsSharedInstanceIfAvailable() {
        $injector = new Injector;
        $injector->define('Auryn\Test\RequiresInterface', array('dep' => 'Auryn\Test\DepImplementation'));
        $injector->share('Auryn\Test\RequiresInterface');
        $injected = $injector->make('Auryn\Test\RequiresInterface');

        $this->assertEquals('something', $injected->testDep->testProp);
        $injected->testDep->testProp = 'something else';

        $injected2 = $injector->make('Auryn\Test\RequiresInterface');
        $this->assertEquals('something else', $injected2->testDep->testProp);
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnClassLoadFailure() {
        $injector = new Injector;
        $injector->make('ClassThatDoesntExist');
    }

    public function testMakeInstanceUsesCustomDefinitionIfSpecified() {
        $injector = new Injector;
        $injector->define('Auryn\Test\TestNeedsDep', array('testDep'=>'Auryn\Test\TestDependency'));
        $injected = $injector->make('Auryn\Test\TestNeedsDep', array('testDep'=>'Auryn\Test\TestDependency2'));
        $this->assertEquals('testVal2', $injected->testDep->testProp);
    }

    public function testMakeInstanceCustomDefinitionOverridesExistingDefinitions() {
        $injector = new Injector;
        $injector->define('Auryn\Test\InjectorTestChildClass', array(':arg1'=>'First argument', ':arg2'=>'Second argument'));
        $injected = $injector->make('Auryn\Test\InjectorTestChildClass', array(':arg1'=>'Override'));
        $this->assertEquals('Override', $injected->arg1);
        $this->assertEquals('Second argument', $injected->arg2);
    }

    public function testMakeInstanceStoresShareIfMarkedWithNullInstance() {
        $injector = new Injector;
        $injector->share('Auryn\Test\TestDependency');
        $injector->make('Auryn\Test\TestDependency');
    }

    public function testMakeInstanceUsesReflectionForUnknownParamsInMultiBuildWithDeps() {
        $injector = new Injector;
        $obj = $injector->make('Auryn\Test\TestMultiDepsWithCtor', array('val1'=>'Auryn\Test\TestDependency'));
        $this->assertInstanceOf('Auryn\Test\TestMultiDepsWithCtor', $obj);

        $obj = $injector->make('Auryn\Test\NoTypehintNoDefaultConstructorClass',
            array('val1'=>'Auryn\Test\TestDependency')
        );
        $this->assertInstanceOf('Auryn\Test\NoTypehintNoDefaultConstructorClass', $obj);
        $this->assertEquals(NULL, $obj->testParam);
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefault() {
        $injector = new Injector;
        $obj = $injector->make('Auryn\Test\InjectorTestCtorParamWithNoTypehintOrDefault');
        $this->assertNull($obj->val);
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefaultThroughAliasedTypehint() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\TestNoExplicitDefine', 'Auryn\Test\InjectorTestCtorParamWithNoTypehintOrDefault');
        $injector->make('Auryn\Test\InjectorTestCtorParamWithNoTypehintOrDefaultDependent');
    }

    /**
     * @TODO
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnUninstantiableTypehintWithoutDefinition() {
        $injector = new Injector;
        $obj = $injector->make('Auryn\Test\RequiresInterface');
    }

    public function testTypelessDefineForDependency() {
        $thumbnailSize = 128;
        $injector = new Injector;
        $injector->defineParam('thumbnailSize', $thumbnailSize);
        $testClass = $injector->make('Auryn\Test\RequiresDependencyWithTypelessParameters');
        $this->assertEquals($thumbnailSize, $testClass->getThumbnailSize(), 'Typeless define was not injected correctly.');
    }

    public function testTypelessDefineForAliasedDependency() {
        $injector = new Injector;
        $injector->defineParam('val', 42);

        $injector->alias('Auryn\Test\TestNoExplicitDefine', 'Auryn\Test\ProviderTestCtorParamWithNoTypehintOrDefault');
        $obj = $injector->make('Auryn\Test\ProviderTestCtorParamWithNoTypehintOrDefaultDependent');
    }

    public function testMakeInstanceInjectsRawParametersDirectly() {
        $injector = new Injector;
        $injector->define('Auryn\Test\InjectorTestRawCtorParams', array(
            ':string' => 'string',
            ':obj' => new \StdClass,
            ':int' => 42,
            ':array' => array(),
            ':float' => 9.3,
            ':bool' => true,
            ':null' => null,
        ));

        $obj = $injector->make('Auryn\Test\InjectorTestRawCtorParams');
        $this->assertInternalType('string', $obj->string);
        $this->assertInstanceOf('StdClass', $obj->obj);
        $this->assertInternalType('int', $obj->int);
        $this->assertInternalType('array', $obj->array);
        $this->assertInternalType('float', $obj->float);
        $this->assertInternalType('bool', $obj->bool);
        $this->assertNull($obj->null);
    }

    /**
     * @TODO
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionWhenDelegateDoes() {
        $injector= new Injector;

        $callable = $this->getMock(
            'CallableMock',
            array('__invoke')
        );

        $injector->delegate('TestDependency', $callable);

        $callable->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new \Auryn\InjectorException()));

        $injector->make('TestDependency');
    }

    public function testMakeInstanceHandlesNamespacedClasses() {
        $injector = new Injector;
        $injector->make('Auryn\Test\SomeClassName');
    }

    public function testMakeInstanceDelegate() {
        $injector= new Injector;

        $callable = $this->getMock(
            'CallableMock',
            array('__invoke')
        );
        $callable->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue(new TestDependency()));

        $injector->delegate('Auryn\Test\TestDependency', $callable);

        $obj = $injector->make('Auryn\Test\TestDependency');

        $this->assertInstanceOf('Auryn\Test\TestDependency', $obj);
    }

    public function testMakeInstanceWithStringDelegate() {
        $injector= new Injector;
        $injector->delegate('StdClass', 'Auryn\Test\StringStdClassDelegateMock');
        $obj = $injector->make('StdClass');
        $this->assertEquals(42, $obj->test);
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassHasNoInvokeMethod() {
        $injector= new Injector;
        $injector->delegate('StdClass', 'StringDelegateWithNoInvokeMethod');
        $obj = $injector->make('StdClass');
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassInstantiationFails() {
        $injector= new Injector;
        $injector->delegate('StdClass', 'SomeClassThatDefinitelyDoesNotExistForReal');
        $obj = $injector->make('StdClass');
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithNoDefinition() {
        $injector = new Injector;
        $obj = $injector->make('Auryn\Test\RequiresInterface');
    }

    public function testDefineAssignsPassedDefinition() {
        $injector = new Injector;
        $definition = array('dep' => 'Auryn\Test\DepImplementation');
        $injector->define('Auryn\Test\RequiresInterface', $definition);
        $this->assertInstanceOf('Auryn\Test\RequiresInterface', $injector->make('Auryn\Test\RequiresInterface'));
    }

    public function testShareStoresSharedInstanceAndReturnsCurrentInstance() {
        $injector = new Injector;
        $testShare = new \StdClass;
        $testShare->test = 42;

        $this->assertInstanceOf('Auryn\Injector', $injector->share($testShare));
        $testShare->test = 'test';
        $this->assertEquals('test', $injector->make('stdclass')->test);
    }

    public function testShareMarksClassSharedOnNullObjectParameter() {
        $injector = new Injector;
        $this->assertInstanceOf('Auryn\Injector', $injector->share('SomeClass'));
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testShareThrowsExceptionOnInvalidArgument() {
        $injector = new Injector;
        $injector->share(42);
    }

    public function testAliasAssignsValueAndReturnsCurrentInstance() {
        $injector = new Injector;
        $this->assertInstanceOf('Auryn\Injector', $injector->alias('DepInterface', 'Auryn\Test\DepImplementation'));
    }

    public function provideInvalidDelegates() {
        return array(
            array(new \StdClass),
            array(42),
            array(true)
        );
    }

    /**
     * @dataProvider provideInvalidDelegates
     * @expectedException Auryn\InjectorException
     */
    public function testDelegateThrowsExceptionIfDelegateIsNotCallableOrString($badDelegate) {
        $injector = new Injector;
        $injector->delegate('Auryn\Test\TestDependency', $badDelegate);
    }

    public function testDelegateInstantiatesCallableClassString() {
        $injector = new Injector;
        $injector->delegate('Auryn\Test\MadeByDelegate', 'Auryn\Test\CallableDelegateClassTest');
        $this->assertInstanceof('Auryn\Test\MadeByDelegate', $injector->make('Auryn\Test\MadeByDelegate'));
    }

    public function testDelegateInstantiatesCallableClassArray() {
        $injector = new Injector;
        $injector->delegate('Auryn\Test\MadeByDelegate', array('Auryn\Test\CallableDelegateClassTest', '__invoke'));
        $this->assertInstanceof('Auryn\Test\MadeByDelegate', $injector->make('Auryn\Test\MadeByDelegate'));
    }

    /**
     * @dataProvider provideExecutionExpectations
     */
    public function testProvisionedInvokables($toInvoke, $definition, $expectedResult) {
        $injector = new Injector;
        $this->assertEquals($expectedResult, $injector->execute($toInvoke, $definition));
    }

    public function provideExecutionExpectations() {
        $return = array();

        // 0 -------------------------------------------------------------------------------------->

        $toInvoke = array('Auryn\Test\ExecuteClassNoDeps', 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 1 -------------------------------------------------------------------------------------->

        $toInvoke = array(new ExecuteClassNoDeps, 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 2 -------------------------------------------------------------------------------------->

        $toInvoke = array('Auryn\Test\ExecuteClassDeps', 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 3 -------------------------------------------------------------------------------------->

        $toInvoke = array(new ExecuteClassDeps(new TestDependency), 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 4 -------------------------------------------------------------------------------------->

        $toInvoke = array('Auryn\Test\ExecuteClassDepsWithMethodDeps', 'execute');
        $args = array(':arg' => 9382);
        $expectedResult = 9382;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 5 -------------------------------------------------------------------------------------->

        $toInvoke = array('Auryn\Test\ExecuteClassStaticMethod', 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 6 -------------------------------------------------------------------------------------->

        $toInvoke = array(new ExecuteClassStaticMethod, 'execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 7 -------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassStaticMethod::execute';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 8 -------------------------------------------------------------------------------------->

        $toInvoke = array('Auryn\Test\ExecuteClassRelativeStaticMethod', 'parent::execute');
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 9 -------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\testExecuteFunction';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 10 ------------------------------------------------------------------------------------->

        $toInvoke = function() { return 42; };
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 11 ------------------------------------------------------------------------------------->

        $toInvoke = new ExecuteClassInvokable;
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 12 ------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassInvokable';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 13 ------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassNoDeps::execute';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 14 ------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassDeps::execute';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 15 ------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassStaticMethod::execute';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // 16 ------------------------------------------------------------------------------------->

        $toInvoke = 'Auryn\Test\ExecuteClassRelativeStaticMethod::parent::execute';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);


        $toInvoke = 'Auryn\Test\testExecuteFunctionWithArg';
        $args = array();
        $expectedResult = 42;
        $return[] = array($toInvoke, $args, $expectedResult);

        // x -------------------------------------------------------------------------------------->

        return $return;
    }

    public function testStaticStringInvokableWithArgument() {
        $injector = new \Auryn\Injector;
        $invokable = $injector->buildExecutable('Auryn\Test\ClassWithStaticMethodThatTakesArg::doSomething');
        $this->assertEquals(42, $invokable(41));

    }

    public function testInterfaceFactoryDelegation() {
        $injector = new Injector;
        $injector->delegate('Auryn\Test\DelegatableInterface', 'Auryn\Test\ImplementsInterfaceFactory');
        $requiresDelegatedInterface = $injector->make('Auryn\Test\RequiresDelegatedInterface');
        $requiresDelegatedInterface->foo();
    }

    /**
     * @expectedException Auryn\InjectorException
     */
    public function testMissingAlias() {
        $injector = new Injector;
        $testClass = $injector->make('Auryn\Test\TestMissingDependency');
    }

    public function testAliasingConcreteClasses(){
        $injector = new Injector;
        $injector->alias('Auryn\Test\ConcreteClass1', 'Auryn\Test\ConcreteClass2');
        $obj = $injector->make('Auryn\Test\ConcreteClass1');
        $this->assertInstanceOf('Auryn\Test\ConcreteClass2', $obj);
    }

    public function testSharedByAliasedInterfaceName() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\SharedClass');
        $injector->share('Auryn\Test\SharedAliasedInterface');
        $class = $injector->make('Auryn\Test\SharedAliasedInterface');
        $class2 = $injector->make('Auryn\Test\SharedAliasedInterface');
        $this->assertSame($class, $class2);
    }

    public function testNotSharedByAliasedInterfaceName() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\SharedClass');
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\NotSharedClass');
        $injector->share('Auryn\Test\SharedClass');
        $class = $injector->make('Auryn\Test\SharedAliasedInterface');
        $class2 = $injector->make('Auryn\Test\SharedAliasedInterface');

        $this->assertNotSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameReversedOrder() {
        $injector = new Injector;
        $injector->share('Auryn\Test\SharedAliasedInterface');
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\SharedClass');
        $class = $injector->make('Auryn\Test\SharedAliasedInterface');
        $class2 = $injector->make('Auryn\Test\SharedAliasedInterface');
        $this->assertSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameWithParameter() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\SharedClass');
        $injector->share('Auryn\Test\SharedAliasedInterface');
        $sharedClass = $injector->make('Auryn\Test\SharedAliasedInterface');
        $childClass = $injector->make('Auryn\Test\ClassWithAliasAsParameter');
        $this->assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testSharedByAliasedInstance() {
        $injector = new Injector;
        $injector->alias('Auryn\Test\SharedAliasedInterface', 'Auryn\Test\SharedClass');
        $sharedClass = $injector->make('Auryn\Test\SharedAliasedInterface');
        $injector->share($sharedClass);
        $childClass = $injector->make('Auryn\Test\ClassWithAliasAsParameter');
        $this->assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testMultipleShareCallsDontOverrideTheOriginalSharedInstance() {
        $injector = new Injector;
        $injector->share('StdClass');
        $stdClass1 = $injector->make('StdClass');
        $injector->share('StdClass');
        $stdClass2 = $injector->make('StdClass');
        $this->assertSame($stdClass1, $stdClass2);
    }

    public function testDependencyWhereSharedWithProtectedConstructor() {
        $injector = new Injector;

        $inner = TestDependencyWithProtectedConstructor::create();
        $injector->share($inner);

        $outer = $injector->make('Auryn\Test\TestNeedsDepWithProtCons');

        $this->assertSame($inner, $outer->dep);
    }

    public function testDependencyWhereShared() {
        $injector = new Injector;
        $injector->share('Auryn\Test\ClassInnerB');
        $innerDep = $injector->make('Auryn\Test\ClassInnerB');
        $inner = $injector->make('Auryn\Test\ClassInnerA');
        $this->assertSame($innerDep, $inner->dep);
        $outer = $injector->make('Auryn\Test\ClassOuter');
        $this->assertSame($innerDep, $outer->dep->dep);
    }

    public function testBugWithReflectionPoolIncorrectlyReturningBadInfo() {
        $injector = new Injector;
        $obj = $injector->make('Auryn\Test\ClassOuter');
        $this->assertInstanceOf('Auryn\Test\ClassOuter', $obj);
        $this->assertInstanceOf('Auryn\Test\ClassInnerA', $obj->dep);
        $this->assertInstanceOf('Auryn\Test\ClassInnerB', $obj->dep->dep);
    }

    public function provideCyclicDependencies() {
        return array(
            'Auryn\Test\RecursiveClassA' => array('Auryn\Test\RecursiveClassA'),
            'Auryn\Test\RecursiveClassB' => array('Auryn\Test\RecursiveClassB'),
            'Auryn\Test\RecursiveClassC' => array('Auryn\Test\RecursiveClassC'),
            'Auryn\Test\RecursiveClass1' => array('Auryn\Test\RecursiveClass1'),
            'Auryn\Test\RecursiveClass2' => array('Auryn\Test\RecursiveClass2'),
            'Auryn\Test\DependsOnCyclic' => array('Auryn\Test\DependsOnCyclic'),
        );
    }

     /**
     * @dataProvider provideCyclicDependencies
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_CYCLIC_DEPENDENCY
     */
    public function testCyclicDependencies($class) {
        $injector = new Injector;
        $injector->make($class);
    }

    public function testNonConcreteDependencyWithDefaultValue() {
        $injector = new Injector;
        $class = $injector->make('Auryn\Test\NonConcreteDependencyWithDefaultValue');
        $this->assertInstanceOf('Auryn\Test\NonConcreteDependencyWithDefaultValue', $class);
        $this->assertNull($class->interface);
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_ALIASED_CANNOT_SHARE
     */
    public function testShareAfterAliasException() {
        $injector = new Injector();
        $testClass = new \StdClass();
        $injector->alias('StdClass', 'Auryn\Test\SomeOtherClass');
        $injector->share($testClass);
    }

    public function testShareAfterAliasAliasedClassAllowed() {
        $injector = new Injector();
        $testClass = new DepImplementation();
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
        $injector->share($testClass);
        $obj = $injector->make('Auryn\Test\DepInterface');
        $this->assertInstanceOf('Auryn\Test\DepImplementation', $obj);
    }

    public function testAliasAfterShareByStringAllowed() {
        $injector = new Injector();
        $injector->share('Auryn\Test\DepInterface');
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
        $obj = $injector->make('Auryn\Test\DepInterface');
        $obj2 = $injector->make('Auryn\Test\DepInterface');
        $this->assertInstanceOf('Auryn\Test\DepImplementation', $obj);
        $this->assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareBySharingAliasAllowed() {
        $injector = new Injector();
        $injector->share('Auryn\Test\DepImplementation');
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
        $obj = $injector->make('Auryn\Test\DepInterface');
        $obj2 = $injector->make('Auryn\Test\DepInterface');
        $this->assertInstanceOf('Auryn\Test\DepImplementation', $obj);
        $this->assertEquals($obj, $obj2);
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_SHARED_CANNOT_ALIAS
     */
    public function testAliasAfterShareException() {
        $injector = new Injector();
        $testClass = new \StdClass();
        $injector->share($testClass);
        $injector->alias('StdClass', 'Auryn\Test\SomeOtherClass');
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructor() {
        $injector = new Injector();
        $injector->make('Auryn\Test\HasNonPublicConstructor');
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructorWithArgs() {
        $injector = new Injector();
        $injector->make('Auryn\Test\HasNonPublicConstructorWithArgs');
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_INVOKABLE
     */
    public function testMakeExecutableFailsOnNonExistentFunction() {
        $injector = new Injector();
        $injector->buildExecutable('nonExistentFunction');
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_INVOKABLE
     */
    public function testMakeExecutableFailsOnNonExistentMethod() {
        $injector = new Injector();
        $object = new \StdClass();
        $injector->buildExecutable(array($object, 'nonExistentFunction'));
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_INVOKABLE
     */
    public function testMakeExecutableFailsOnClassWithoutInvoke() {
        $injector = new Injector();
        $object = new \StdClass();
        $injector->buildExecutable($object);
    }

    /**
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_NON_EMPTY_STRING_ALIAS
     */
    public function testBadAlias() {
        $injector = new Injector();
        $injector->share('Auryn\Test\DepInterface');
        $injector->alias('Auryn\Test\DepInterface', '');
    }

    public function testShareNewAlias() {
        $injector = new Injector();
        $injector->share('Auryn\Test\DepImplementation');
        $injector->alias('Auryn\Test\DepInterface', 'Auryn\Test\DepImplementation');
    }

    public function testDefineWithBackslashAndMakeWithoutBackslash(){
        $injector = new Injector();
        $injector->define('Auryn\Test\SimpleNoTypehintClass', array(':arg' => 'tested'));
        $testClass = $injector->make('Auryn\Test\SimpleNoTypehintClass');
        $this->assertEquals('tested', $testClass->testParam);
    }

    public function testShareWithBackslashAndMakeWithoutBackslash(){
        $injector = new Injector();
        $injector->share('\StdClass');
        $classA = $injector->make('StdClass');
        $classA->tested = false;
        $classB = $injector->make('\StdClass');
        $classB->tested = true;

        $this->assertEquals($classA->tested, $classB->tested);
    }

    public function testInstanceMutate() {
        $injector = new Injector();
        $injector->prepare('\StdClass', function($obj, $injector) {
            $obj->testval = 42;
        });
        $obj = $injector->make('StdClass');

        $this->assertSame(42, $obj->testval);
    }

    public function testInterfaceMutate() {
        $injector = new Injector();
        $injector->prepare('Auryn\Test\SomeInterface', function($obj, $injector) {
            $obj->testProp = 42;
        });
        $obj = $injector->make('Auryn\Test\PreparesImplementationTest');

        $this->assertSame(42, $obj->testProp);
    }



    /**
     * Test that custom definitions are not passed through to dependencies.
     * Surprising things would happen if this did occur.
     * @expectedException \Auryn\InjectorException
     * @expectedExceptionCode \Auryn\Injector::E_UNDEFINED_PARAM
     */
    public function testCustomDefinitionNotPassedThrough() {
        $injector = new Injector();
        $injector->share('Auryn\Test\DependencyWithDefinedParam');
        $injector->make('Auryn\Test\RequiresDependencyWithDefinedParam', array(':foo' => 5));
    }
}
