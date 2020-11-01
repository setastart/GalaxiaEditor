# important:
- MySQL TEXT columns can't have a default value, so they should be nullable




# Convention: MySQL nullable columns should not have empty strings like '' as values, and use null instead.


MySQL can use both null and '' to store values in nullable columns.


In the beginning, galaxia made things simpler by using only ''.
This lead to some denormalization, and worked fine until more complex cases arrived.
For more complex cases, this meant too much denormalization, so allowing null made things simpler.


The problem are not nulls, it's mixing nulls with empty strings such as '', because:
In order to keep editing simple, Galaxia inputs on POST are always sent as string, so there is no way to send a null, only an empty string.


So the current idea is that inputs can be nullable, and they're not by default.
Nullable inputs can only be used on nullable columns.


The solution is:

***When validating, if  the input is nullable, treat '' as a null.***




# What should use empty strings and not be nullable?
    - VARCHAR columns such as titles

# What inputs and MySQL columns should be nullable and never use an empty string?
    - TEXT columns, because they can't have default values
    - datetime, date, time columns that are optional
    - foreign keys that can be set to null, like if a page has no tags
    - columns that need to bypass unique constraints


inputs that are     nullable send '' and null as null
inputs that are NOT nullable send '' and null as ''



To make it simple handling various data types, we convert every value from the database to string, EXCEPT null.



To explain, imagine we have two tables: page [pageId] and tag [tagId, tagTitle].
We want to associate tags with pages:


For some cases, we can avoid nullable columns:

    If we want to have exactly one tag per page, we don't need nullable:
        - We can have a foreign-key column in page: tagId

    If we want to have zero or one tag per page:
        - We could add a "no tag" tag with empty title. NOT GOOD because a "no tag" is a tag in other contexts.
        - Denormalize the table, like next case below:

    If we want to have zero, one or more tags per page:
        - we need a new table with foreign keys that connect pages with tags: pageTag [pageId, tagId]
        - on empty tag input, delete the record.
        - If we don't want duplicate tags:
            - make a unique constraint on pageTag.tagId
        - if tag has another column that has a unique constraint




If we add more complexity, we can solve it with allowinh nulls oradding more denormalization:

    If we want to have zero or one tag per page:
        - use null if no tag

    If we want to have zero, one or more tags per page:
        - we need a new table with foreign keys that connect pages with tags: pageTag [pageId, tagId]
        - on empty tag input, delete the record.
        - If we don't want duplicate tags:
            - make a unique constraint on pageTag.tagId
        - if tag has another column that has a unique constraint
            - make that constraint column nullable







        - same as above, but tagId must be nullable, and for zero tags, it should be set to null


We often need nullable inputs:
    - to break a foreign key constraint (ex. if we have a tags column and we don't want to chose any tag for the page)
        - we could have a special tag for "no tag", but only if we're
    - to bypass unique constraints
    - datetime, date, time columns to indicate no no value

Methods of avoiding use of nulls
    - denormalization, add a new table pageTags and if tag is empty, delete the record




We use MySQL unique constraints where needed.
For example page slugs should be unique for each language, even the empty string '' for home.

in MySQL null bypasses unique constraints, so there can be multiple null values for a column with unique constraint.





This is why we have nullable inputs:





page slugs are not nullable, as there can be a '' slug for home.


on galaxia database load, all values are converted to string except null

inputs that are     nullable treat '' and null as null
inputs that are NOT nullable treat '' and null as ''



db                  ''      null    1       'a'

load                ''      null    '1'     'a'

input val           ''      ''      '1'     'a'

input valFromDb     ''      null    '1'     'a'

post                ''      ''      '1'     'a'

post nullable       null    null    '1'     'a'




