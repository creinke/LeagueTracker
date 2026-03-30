<?php
namespace App\Form\Type;

use App\Entity\CourseDE;
use App\Entity\EventDE;
use App\Entity\NineDE;
use App\Entity\SessionDE;
use App\Entity\TeeDE;
use App\Model\EventFormatType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class EventType extends AbstractType {
	private $em;
	
	public function __construct(EntityManagerInterface $entityManager) {
		$this->em = $entityManager;
	}
	
	/**
     * @param $collection
     * @return array
     */
    private function buildChoices($collection) : array {
        $choices = [];
        foreach($collection as $element) {
            $key = $element->getName();
            $choices[$key] = $key;
        }
        return $choices;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $courses = $options['courses'];
    	$sessions = $options['sessions'];
		
        $eventTypeChoices = array();
        $eventTypes = \App\Model\EventType::values();
        foreach($eventTypes as $eventType => $eventTypeValue) {
            $eventTypeChoices[$eventTypeValue] = $eventType;
        }
        $eventFormatChoices = array();
        $eventFormats = EventFormatType::values();
        foreach($eventFormats as $eventFormat => $eventFormatValue) {
            $eventFormatChoices[$eventFormatValue] = $eventFormat;
        }

        $builder
            ->add('eventnumber', NumberType::class,
                ['label' => 'Event Number', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('eventtype', ChoiceType::class,
                ['required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'], 'choices'  => $eventTypeChoices])
            ->add('format', ChoiceType::class,
                ['required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'], 'choices'  => $eventFormatChoices])
            ->add('playersperteam', NumberType::class, 
                ['label' => 'Number of Players per Team', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('teamsorplayerspergame', NumberType::class, 
                ['label' => 'Number of Teams or Players per Game', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('startdateandtime', DateTimeType::class,
                ['label' => 'Starting Date and Time (MM/dd/yyyy, hh:mm a)', 'widget' => 'single_text', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('minutesbetweengames', NumberType::class,
                ['label' => 'Minutes Between Games', 'required' => true, 'attr' => ['class' => 'form-control']])
            ->add('mixedteesenabled', CheckboxType::class,
                ['label' => 'Mixed Tees (Checked = true)', 'required' => false, 'attr' => ['style' => 'width: 10%;', 'class' => 'form-control']])
            ->add('withhandicapping', CheckboxType::class,
                ['label' => 'With Handicapping (Checked = true)', 'required' => false, 'attr' => ['style' => 'width: 10%;', 'class' => 'form-control']])
            ->add('session', ChoiceType::class,
                ['label' => 'Session', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                'choices'  => $sessions, 'choice_label' => function(SessionDE $session, $key, $value) {return $session->getName();}])
            ->add('course', ChoiceType::class,
                ['label' => 'Course', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                'choices'  => $courses, 'choice_label' => function(CourseDE $course, $key, $value) {return $course->getName();}]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) {
            // Get the current form
            $form = $formEvent->getForm();
            // Get the data for this form (in this case it's the sub form's entity) not the main form's entity.
            $event = $formEvent->getData();

            $nineChoices = array();
            $em = $this->em;
			$nullNineChoice = new NineDE($em);
			$nullNineChoice->setName('');
            $nineChoices[' '] = $nullNineChoice;
            foreach($event->getCourse()->getNines() as $nine) {
            	$nineChoices[$nine->getName()] = $nine;
            }
            
            $form
                ->add('nine', ChoiceType::class,
                    ['label' => 'Nine', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                    'choices' => $event->getCourse()->getNines(), 'choice_label' => function(NineDE $nine, $key, $value) {return $nine->getName();}])
                ->add('secondnine', ChoiceType::class,
                	['label' => 'Second Nine', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                    'choices' => $nineChoices, 'choice_label' => function(NineDE $secondnine, $key, $value) {return $secondnine->getName();}])
                ->add('tee', ChoiceType::class,
                    ['label' => 'Tee', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                    'choices' => $event->getNine()->getTees(), 'choice_label' => function(TeeDE $tee, $key, $value) {return $tee->getName();}]);
        });
 
        $formModifier =
            function (FormInterface $form, ?CourseDE $course = null) {
            	$nineChoices = array();
            	$em = $this->em;
            	$nineChoices[' '] = new NineDE($em);
            	foreach($course->getNines() as $nine) {
            		$nineChoices[$nine->getName()] = $nine;
            	}
            	
            	$form
                    ->add('nine', ChoiceType::class,
                        ['label' => 'Nine', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                        'choices' => $course->getNines(), 'choice_label' => function(NineDE $nine, $key, $value) {return $nine->getName();}])
                    ->add('secondnine', ChoiceType::class,
                    	['label' => 'Second Nine', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                        'choices' => $nineChoices, 'choice_label' => function(NineDE $secondnine, $key, $value) {return $secondnine->getName();}])
                    ->add('tee', ChoiceType::class,
                        ['label' => 'Tee', 'required' => true, 'attr' => ['class' => 'form-control', 'style' => 'height: 3.5em;'],
                        'choices' => $course->getNines()[0]->getTees(), 'choice_label' => function(TeeDE $tee, $key, $value) {return $tee->getName();}]);
            };

        $builder->get('course')->addEventListener(FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as $event->getData() will get you the client data (that is, the ID)
                $course = $event->getForm()->getData();

                if ($course == null) {
                    return;
                }
                // Since we've added the listener to the child, we'll have to pass on the parent to the callback functions!
                $formModifier($event->getForm()->getParent(), $course);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(
            [
            	'data_class' => EventDE::class,
            	'courses' => [],
            	'sessions' => []
            ]
        );
        $resolver->setRequired(['courses', 'sessions']);
        $resolver->setAllowedTypes('courses', 'array');
        $resolver->setAllowedTypes('sessions', 'array');
    }
}