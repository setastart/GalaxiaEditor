<?php


use Galaxia\Flash;
use Galaxia\G;
use Galaxia\Sql;
use Galaxia\Text;
use GalaxiaEditor\E;
use GalaxiaEditor\history\History;


unset(E::$itemChanges['passwordCurrent']);
unset(E::$itemChanges['passwordRepeat']);
if (isset(E::$itemChanges['passwordHash']))
    E::$itemChanges['passwordHash'] = password_hash(E::$itemChanges['passwordHash'], PASSWORD_BCRYPT);


$params = array_values(E::$itemChanges);
$params[] = E::$itemId;
$types = str_repeat('s', count($params));

$query = Sql::update($item['gcUpdate']);
$query .= Sql::updateSet(array_keys(E::$itemChanges));
$query .= Sql::updateWhere([$item['gcTable'] => [$item['gcTable'] . 'Id']]);

try {
    $stmt = G::prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows < 1) {
        Flash::error('item-update - Unable to update database.');
    } else {
        foreach (E::$itemChanges as $inputKey => $content) {
            $lang = $item['inputs'][$inputKey]['lang'] ? ' - ' . $item['inputs'][$inputKey]['lang'] : '';
            Flash::info(Text::t('Updated') . ': ' . Text::t($item['inputs'][$inputKey]['label']) . $lang);
            Flash::info(Text::t('Updated'), 'form', $item['inputs'][$inputKey]['name']);

            if ($item['gcTable'] == '_geUser')
                if ($inputKey == 'passwordHash')
                    continue;

            History::insert($uniqueId, $item['gcTable'], E::$itemId, $inputKey, '', 2, $content, $me->id);
        }
    }
} catch (Exception $e) {
    Flash::error('item-update - Unable to save changes to item.');
    geD($e->getMessage(), $query, $types, $params);
    return;
}
