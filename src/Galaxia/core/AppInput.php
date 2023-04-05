<?php


namespace Galaxia;


use function array_key_exists;

class AppInput {

    const typeText     = 'text';
    const typePhone    = 'phone';
    const typeEmail    = 'email';
    const typePassword = 'password';
    const typeTextarea = 'textarea';
    const typeCheckbox = 'checkbox';
    const typeRadio    = 'radio';
    const typeSelect   = 'select';
    const typeButton   = 'button';

    const patternPhone = '[\d\-+\s]*';

    const types = [
        AppInput::typeText,
        AppInput::typePhone,
        AppInput::typeEmail,
        AppInput::typeTextarea,
        AppInput::typeCheckbox,
        AppInput::typeRadio,
        AppInput::typeSelect,
        AppInput::typeButton,
    ];

    public array $error = [];

    const txFormErrorPhone         = 'formErrorPhone';
    const txFormErrorEmail         = 'formErrorEmail';
    const txFormErrorMinlength     = 'formErrorMinlength';
    const txFormErrorMaxlength     = 'formErrorMaxlength';
    const txFormErrorRequired      = 'formErrorRequired';
    const txFormErrorValueNotValid = 'formErrorValueNotValid';
    const txFormContainsSpamWords  = 'formContainsSpamWords';

    const valueYes = 'yes';
    const valueNo  = 'no';

    public string $classWrap  = '';
    public string $classInput = '';
    public string $attr       = '';

    function __construct(
        public string $name,
        public string $label = '',
        public string $value = '',
        public string $type = AppInput::typeText,
        public bool   $required = false,
        public bool   $disabled = false,
        public bool   $autocomplete = false,
        public bool   $autofocus = false,
        public int    $minLength = 0,
        public int    $maxLength = 0,
        public array  $options = [],
        public array  $blockStrings = [],
        public array  $blockWords = [],
    ) {
        if (!in_array($this->type, AppInput::types)) $this->type = AppInput::typeText;
        $this->recalc();
    }


    function recalc(): void {
        $this->classWrap  = "inputWrap inputWrap-$this->type inputName-$this->name";
        $this->classInput = "inputType-$this->type";
        $this->attr       = '';

        if ($this->required) {
            $this->classWrap .= ' required';
            $this->attr      .= ' required';
        }

        if ($this->disabled) {
            $this->classWrap .= ' disabled';
            $this->attr      .= ' disabled';
        }

        if ($this->autocomplete) $this->attr .= ' autocomplete';
        if ($this->autofocus) $this->attr .= ' autofocus';
        if ($this->minLength > 0) $this->attr .= ' minlength="' . Text::h($this->minLength) . '"';
        if ($this->maxLength > 0) $this->attr .= ' maxlength="' . Text::h($this->maxLength) . '"';
    }


    function validate(string $value): bool {
        $this->value = strip_tags(trim($value));

        switch ($this->type) {
            case AppInput::typeText:
                $this->value = preg_replace('/\s\s+/', ' ', $this->value);
                break;

            case AppInput::typePhone:
                $this->value = preg_replace('/\s\s+/', ' ', $this->value);
                if ($this->value) {
                    if (!preg_match('/^' . AppInput::patternPhone . '$/', $this->value)) {
                        $this->error[] = Text::t(AppInput::txFormErrorPhone);
                    }
                }
                break;

            case AppInput::typeEmail:
                $this->value = preg_replace('/\s\s+/', ' ', $this->value);
                if ($this->value || $this->required) {
                    if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
                        $this->error[] = Text::t(AppInput::txFormErrorEmail);
                    }
                }
                break;

            case AppInput::typeRadio:
            case AppInput::typeSelect:
                if (!array_key_exists($this->value, $this->options)) {
                    $this->error[] = Text::t(AppInput::txFormErrorValueNotValid);
                }

            case AppInput::typeCheckbox:
            case AppInput::typeTextarea:
            case AppInput::typeButton:
                break;

            default:
                return false;
        }

        if ($this->required && $this->minLength > 0) {
            if (mb_strlen($this->value) < $this->minLength)
                $this->error[] = sprintf(Text::t(AppInput::txFormErrorMinlength), $this->minLength);
        }

        if ($this->required && $this->maxLength > 0) {
            if (mb_strlen($this->value) > $this->maxLength)
                $this->error[] = sprintf(Text::t(AppInput::txFormErrorMaxlength), $this->maxLength);
        }

        if ($this->required && !$this->value) {
            $this->error[] = Text::t(AppInput::txFormErrorRequired);
        }

        if ($this->blockStrings) {
            $keywordsFound = [];
            foreach ($this->blockStrings as $blockString) {
                if (str_contains(mb_strtolower($this->value), mb_strtolower($blockString))) {
                    $keywordsFound[] = $blockString;
                }
            }
            if ($keywordsFound) {
                $this->error[] = Text::t(AppInput::txFormContainsSpamWords) . ': ' . implode(', ', $keywordsFound);
            }
        }

        if ($this->error) {
            $this->classWrap  .= ' invalid';
            $this->classInput .= ' init';
            $this->attr       .= ' invalid';
        }

        return empty($this->error);
    }


    function string(
        string $classWrap = '',
        string $classBtn = 'btn',
        string $classLabel = 'inputLabel',
    ): string {
        $classWrap  = trim($classWrap . ' ' . Text::h($this->classWrap) ?? '');
        $classInput = trim(Text::h($this->classInput) ?? '');
        $classLabel = trim(Text::h($classLabel));
        $classBtn   = trim(Text::h($classBtn));
        $attr       = trim(Text::h($this->attr) ?? '');
        if ($attr) $attr = ' ' . $attr;

        $name  = Text::h($this->name);
        $label = Text::html($this->label);
        $value = Text::h($this->value);

        ob_start();

        switch ($this->type) {
            case AppInput::typeText:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <input class="<?=$classInput?>" type="text" id="<?=$name?>" name="<?=$name?>" value="<?=$value?>"<?=$attr?>>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typePassword:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <input class="<?=$classInput?>" type="password" id="<?=$name?>" name="<?=$name?>" value="<?=$value?>"<?=$attr?>>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typePhone:

// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <input class="<?=$classInput?>" type="tel" pattern="<?=AppInput::patternPhone?>" id="<?=$name?>" name="<?=$name?>" value="<?=$value?>"<?=$attr?>>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeEmail:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <input class="<?=$classInput?>" type="email" id="<?=$name?>" name="<?=$name?>" value="<?=$value?>"<?=$attr?>>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeTextarea:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <textarea class="<?=$classInput?>" rows="4" id="<?=$name?>" name="<?=$name?>"<?=$attr?>><?=$value?></textarea>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeCheckbox:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <div class="checkboxOptions">
<?php
                foreach ($this->options as $option => $optionText) {
                    $option = Text::h($option) ?? '';
                    $optionText = Text::h($optionText) ?? '';
                    $checked = ($this->value == $option) ? ' checked' : '';
                    $active = ($this->value == $option) ? 'active' : '';
?>
        <label class="inputCheckboxWrap">
            <input class="<?=$classInput?>" type="checkbox" id="<?=$name?><?=$option?>" name="<?=$name?>" value="<?=$option?>"<?=$attr, $checked?>>
            <span class="checkboxMark"></span>
            <span class="checkboxText"><?=$optionText?></span>
        </label>
<?php           } ?>
    </div>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeRadio:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label class="<?=$classLabel?>"><?=$label?></label>
    <div class="inputOptions">
<?php
                foreach ($this->options as $option => $optionText) {
                    $option = Text::h($option) ?? '';
                    $optionText = Text::h($optionText) ?? '';
                    $checked = ($this->value == $option) ? ' checked' : '';
                    $active = ($this->value == $option) ? 'active' : '';
?>
        <label for="<?=$name?><?=$option?>" class="<?=$active?>">
            <input class="<?=$classInput?>" type="radio" id="<?=$name?><?=$option?>" name="<?=$name?>" value="<?=$option?>"<?=$attr, $checked?>>
            <span class="radioMark"></span>
            <span class="radioText"><?=$optionText?></span>
        </label>
<?php           } ?>
    </div>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeSelect:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <label for="<?=$name?>" class="<?=$classLabel?>"><?=$label?></label>
    <select class="<?=$classInput?>" name="<?=$name?>" id="<?=$name?>"<?=$attr?>>
<?php
                foreach ($this->options as $option => $optionText) {
                    $option = Text::h($option);
                    $selected = ($this->value == $option) ? ' selected' : '';
?>
        <option value="<?=$option?>"<?=$selected?>><?=Text::h($optionText)?></option>
<?php           } ?>
    </select>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;


            case AppInput::typeButton:
// @formatter:off ?>
<div class="<?=$classWrap?>">
    <button class="<?=$classBtn?>" type="submit"<?=$attr?>>
        <span><?=$label?></span>
    </button>
<?php $this->renderErrors()?>
</div>
<?php // @formatter:on
                break;

        }

        return ob_get_clean();
    }



    function renderErrors(): void {
        if (!$this->error) return;

// @formatter:off ?>
    <ul class="inputErrors">
<?php       foreach ($this->error as $error) { ?>
        <li class="inputError"><?=Text::h($error)?></li>
<?php       } ?>
    </ul>
<?php // @formatter:on

    }




    static function isRandomOneWordSpam(string $str): bool {
        if (str_contains($str, ' ')) return false;
        if (strlen($str) < 8) return false;
        $upper = similar_text($str, mb_strtolower($str));
        $lower = mb_strlen($str) - $upper;
        if (!$lower) return false;
        $ratio = $upper / $lower;
        return ($ratio > 0.4) && ($ratio < 3.0);
    }

}
