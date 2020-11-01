# Editor
- input
    - checkbox
    - color picker
    - image crop
    - file upload
- list/item
    - modules
        - article
    - item tabs (when gcItem is and array of gcItems)
- files/file
    - uploaded files (pdf, others?)


# Galaxia
## Errors and messages
- move error.php into Msg class 
    - ??? static methods receive $user as parameter?
- use fallback when $_SESSION is unavailable
    - work in CLI 
        - messages are only available until end of current script
        - display messages on script end
    - work on logged out user
        - messages are only available until end of current script
        - ??? make fallback for redirects storing messages in a cookie?
        

- ??? gcSelectLJoin[$table][$cols] could be gcSelectLJoin[$table] = $col because I'm never using more than one col

- Remove dependence on $_SERVER everywhere, so everything it works on CLI.
