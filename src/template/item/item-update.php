<?php


unset($itemChanges['passwordCurrent']);
unset($itemChanges['passwordRepeat']);
if (isset($itemChanges['passwordHash']))
    $itemChanges['passwordHash'] = password_hash($itemChanges['passwordHash'], PASSWORD_BCRYPT);


$params = array_values($itemChanges);
$params[] = $itemId;
$types = str_repeat('s', count($params));

$query = queryUpdate($item['gcUpdate']);
$query .= queryUpdateSet(array_keys($itemChanges));
$query .= queryUpdateWhere([$item['gcTable'] => [$item['gcTable'] . 'Id']]);

try {
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows < 1) {
        error('item-update - Unable to update database.');
    } else {
        foreach ($itemChanges as $inputKey => $content) {
            $lang = $item['inputs'][$inputKey]['lang'] ? ' - ' . $item['inputs'][$inputKey]['lang'] : '';
            info(t('Updated') . ': ' . t($item['inputs'][$inputKey]['label']) . $lang);
            info(t('Updated'), 'form', $item['inputs'][$inputKey]['name']);

            if ($item['gcTable'] == '_geUser')
                if ($inputKey == 'passwordHash')
                    continue;

            insertHistory($uniqueId, $item['gcTable'], $itemId, $inputKey, '', 2, $content, $me->id);
        }
    }
} catch (Exception $e) {
    error('item-update - Unable to save changes to item.');
    geD($e->getMessage(), $query, $types, $params);
    return;
}
