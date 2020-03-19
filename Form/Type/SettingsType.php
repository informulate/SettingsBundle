<?php

/**
 * This file is part of the DmishhSettingsBundle package.
 *
 * (c) 2013 Dmitriy Scherbina <http://dmishh.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmishh\SettingsBundle\Form\Type;

use Dmishh\SettingsBundle\Exception\SettingsException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Settings management form.
 *
 * @author Dmitriy Scherbina <http://dmishh.com>
 * @author Artem Zhuravlov
 */
class SettingsType extends AbstractType
{
    protected $settingsConfiguration;
    protected $translator;

    public function __construct(array $settingsConfiguration, Translator $translator)
    {
        $this->settingsConfiguration = $settingsConfiguration;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->settingsConfiguration as $name => $configuration) {
            // If setting's value exists in data and setting isn't disabled
            if (array_key_exists($name, $options['data']) && !in_array($name, $options['disabled_settings'])) {
                $fieldType = $configuration['type'];

                // There is probably a better way to handle this... but I don't have the time ->jO
                if (false === is_object($fieldType)) {
                    $fieldType = '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\'.ucfirst($fieldType).'Type';
                } else {
                    $fieldType = '\\'.get_class($fieldType);
                }

                $fieldOptions = $configuration['options'];
                $fieldOptions['constraints'] = $configuration['constraints'];

                // Validator constraints
                if (!empty($fieldOptions['constraints']) && is_array($fieldOptions['constraints'])) {
                    $constraints = array();
                    foreach ($fieldOptions['constraints'] as $class => $constraintOptions) {
                        if (class_exists($class)) {
                            $constraints[] = new $class($constraintOptions);
                        } else {
                            throw new SettingsException(sprintf('Constraint class "%s" not found', $class));
                        }
                    }

                    $fieldOptions['constraints'] = $constraints;
                }

                // Label I18n
                $fieldOptions['label'] = 'labels.'.$name;
                $fieldOptions['translation_domain'] = 'settings';

                // Choices I18n
                if (!empty($fieldOptions['choices'])) {
                    $choices = [];
                    foreach ($fieldOptions['choices'] as $choice) {
                        $label = $fieldOptions['label'].'_choices.'.$choice;
                        $choices[$this->translator->trans($label, [], 'settings')] = $choice;
                    }

                    $fieldOptions['choices'] = $choices;
                }
                $builder->add($name, $fieldType, $fieldOptions);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'disabled_settings' => array(),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'settings_management';
    }
}
