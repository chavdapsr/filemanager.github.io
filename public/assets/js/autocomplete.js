// PHP Auto-Complete Tab Functionality
class PHPAutoComplete {
    constructor(textarea) {
        this.textarea = textarea;
        this.suggestions = [];
        this.currentSuggestion = 0;
        this.isVisible = false;
        this.suggestionBox = null;
        
        this.phpKeywords = [
            '<?php', '?>', '<?=', '<?', '?>',
            'function', 'class', 'public', 'private', 'protected',
            'static', 'const', 'var', 'global', 'require', 'require_once',
            'include', 'include_once', 'namespace', 'use', 'as',
            'if', 'else', 'elseif', 'endif', 'switch', 'case', 'default',
            'for', 'foreach', 'while', 'do', 'break', 'continue',
            'return', 'throw', 'try', 'catch', 'finally',
            'new', 'clone', 'instanceof', 'extends', 'implements',
            'interface', 'abstract', 'final', 'trait', 'yield',
            'array', 'string', 'int', 'float', 'bool', 'object',
            'null', 'true', 'false', 'self', 'parent', 'this',
            'echo', 'print', 'isset', 'unset', 'empty', 'die', 'exit'
        ];
        
        this.phpFunctions = [
            'array()', 'count()', 'sizeof()', 'in_array()', 'array_push()',
            'array_pop()', 'array_shift()', 'array_unshift()', 'array_merge()',
            'array_keys()', 'array_values()', 'array_unique()', 'sort()',
            'rsort()', 'asort()', 'arsort()', 'ksort()', 'krsort()',
            'strlen()', 'strpos()', 'str_replace()', 'substr()', 'trim()',
            'strtolower()', 'strtoupper()', 'ucfirst()', 'ucwords()',
            'explode()', 'implode()', 'json_encode()', 'json_decode()',
            'file_get_contents()', 'file_put_contents()', 'fopen()', 'fclose()',
            'fread()', 'fwrite()', 'file_exists()', 'is_file()', 'is_dir()',
            'mkdir()', 'rmdir()', 'unlink()', 'copy()', 'rename()',
            'move_uploaded_file()', 'pathinfo()', 'basename()', 'dirname()',
            'realpath()', 'chmod()', 'chown()', 'fileperms()', 'filesize()',
            'filemtime()', 'filectime()', 'mime_content_type()', 'finfo_open()',
            'password_hash()', 'password_verify()', 'password_needs_rehash()',
            'session_start()', 'session_destroy()', 'session_regenerate_id()',
            '$_SESSION', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SERVER',
            '$_REQUEST', '$_ENV', '$_GLOBALS', '$GLOBALS',
            'header()', 'setcookie()', 'http_response_code()',
            'filter_var()', 'filter_input()', 'htmlspecialchars()',
            'strip_tags()', 'addslashes()', 'stripslashes()',
            'md5()', 'sha1()', 'hash()', 'crypt()', 'bin2hex()',
            'hex2bin()', 'base64_encode()', 'base64_decode()',
            'urlencode()', 'urldecode()', 'rawurlencode()', 'rawurldecode()',
            'date()', 'time()', 'strtotime()', 'mktime()', 'gmdate()',
            'date_default_timezone_set()', 'date_default_timezone_get()',
            'microtime()', 'sleep()', 'usleep()', 'time_nanosleep()',
            'preg_match()', 'preg_match_all()', 'preg_replace()', 'preg_split()',
            'preg_quote()', 'preg_grep()', 'preg_filter()',
            'mysql_connect()', 'mysql_select_db()', 'mysql_query()',
            'mysqli_connect()', 'mysqli_query()', 'mysqli_fetch_array()',
            'mysqli_fetch_assoc()', 'mysqli_fetch_object()', 'mysqli_num_rows()',
            'PDO', 'PDO::ATTR_ERRMODE', 'PDO::ERRMODE_EXCEPTION',
            'PDO::FETCH_ASSOC', 'PDO::FETCH_OBJ', 'PDO::FETCH_NUM',
            'PDO::FETCH_BOTH', 'PDO::FETCH_LAZY', 'PDO::FETCH_BOUND',
            'PDO::FETCH_COLUMN', 'PDO::FETCH_CLASS', 'PDO::FETCH_INTO',
            'PDO::FETCH_FUNC', 'PDO::FETCH_GROUP', 'PDO::FETCH_UNIQUE',
            'PDO::FETCH_KEY_PAIR', 'PDO::FETCH_CLASSTYPE', 'PDO::FETCH_SERIALIZE',
            'PDO::FETCH_PROPS_LATE', 'PDO::FETCH_NAMED', 'PDO::FETCH_NAMED',
            'PDO::FETCH_NAMED', 'PDO::FETCH_NAMED', 'PDO::FETCH_NAMED'
        ];
        
        this.phpClasses = [
            'Exception', 'ErrorException', 'Error', 'ParseError', 'TypeError',
            'ArgumentCountError', 'ArithmeticError', 'DivisionByZeroError',
            'ClosureError', 'PDOException', 'PDO', 'PDOStatement',
            'DateTime', 'DateTimeImmutable', 'DateTimeZone', 'DateInterval',
            'DatePeriod', 'DateTimeInterface', 'DateTimeInterface',
            'SplFileInfo', 'SplFileObject', 'SplTempFileObject',
            'DirectoryIterator', 'RecursiveDirectoryIterator',
            'RecursiveIteratorIterator', 'FilterIterator', 'LimitIterator',
            'CachingIterator', 'CallbackFilterIterator', 'RecursiveCallbackFilterIterator',
            'RecursiveTreeIterator', 'RecursiveArrayIterator', 'ArrayIterator',
            'AppendIterator', 'InfiniteIterator', 'IteratorIterator',
            'MultipleIterator', 'NoRewindIterator', 'ParentIterator',
            'RecursiveArrayIterator', 'RecursiveCachingIterator',
            'RecursiveDirectoryIterator', 'RecursiveFilterIterator',
            'RecursiveIterator', 'RecursiveIteratorIterator', 'RecursiveRegexIterator',
            'RecursiveTreeIterator', 'RegexIterator', 'SeekableIterator',
            'SplDoublyLinkedList', 'SplFixedArray', 'SplHeap', 'SplMaxHeap',
            'SplMinHeap', 'SplObjectStorage', 'SplPriorityQueue', 'SplQueue',
            'SplStack', 'SplSubject', 'SplObserver', 'SplObserver',
            'Countable', 'Iterator', 'IteratorAggregate', 'Serializable',
            'Throwable', 'Traversable', 'ArrayAccess', 'Closure',
            'Generator', 'Reflection', 'ReflectionClass', 'ReflectionFunction',
            'ReflectionMethod', 'ReflectionProperty', 'ReflectionParameter',
            'ReflectionType', 'ReflectionNamedType', 'ReflectionUnionType',
            'ReflectionIntersectionType', 'ReflectionAttribute',
            'ReflectionEnum', 'ReflectionEnumUnitCase', 'ReflectionEnumBackedCase',
            'ReflectionFiber', 'ReflectionGenerator', 'ReflectionObject',
            'ReflectionReference', 'ReflectionZendExtension', 'ReflectionExtension',
            'ReflectionClassConstant', 'ReflectionEnumUnitCase', 'ReflectionEnumBackedCase'
        ];
        
        this.init();
    }
    
    init() {
        this.createSuggestionBox();
        this.bindEvents();
    }
    
    createSuggestionBox() {
        this.suggestionBox = document.createElement('div');
        this.suggestionBox.className = 'php-autocomplete-suggestions';
        this.suggestionBox.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        `;
        
        document.body.appendChild(this.suggestionBox);
    }
    
    bindEvents() {
        this.textarea.addEventListener('keydown', (e) => {
            this.handleKeyDown(e);
        });
        
        this.textarea.addEventListener('input', (e) => {
            this.handleInput(e);
        });
        
        this.textarea.addEventListener('blur', () => {
            this.hideSuggestions();
        });
    }
    
    handleKeyDown(e) {
        if (!this.isVisible) return;
        
        switch(e.key) {
            case 'Tab':
                e.preventDefault();
                this.acceptSuggestion();
                break;
            case 'Enter':
                e.preventDefault();
                this.acceptSuggestion();
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.nextSuggestion();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.previousSuggestion();
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    handleInput(e) {
        const cursorPos = this.textarea.selectionStart;
        const text = this.textarea.value;
        const currentWord = this.getCurrentWord(text, cursorPos);
        
        if (currentWord.length >= 1) {
            this.showSuggestions(currentWord, cursorPos);
        } else {
            this.hideSuggestions();
        }
    }
    
    getCurrentWord(text, cursorPos) {
        const beforeCursor = text.substring(0, cursorPos);
        const words = beforeCursor.split(/[\s\n\r\t\(\)\[\]{};,:]/);
        return words[words.length - 1];
    }
    
    showSuggestions(word, cursorPos) {
        const suggestions = this.getSuggestions(word);
        
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        this.suggestions = suggestions;
        this.currentSuggestion = 0;
        this.isVisible = true;
        
        this.updateSuggestionBox();
        this.positionSuggestionBox(cursorPos);
    }
    
    getSuggestions(word) {
        const allSuggestions = [
            ...this.phpKeywords,
            ...this.phpFunctions,
            ...this.phpClasses
        ];
        
        return allSuggestions.filter(suggestion => 
            suggestion.toLowerCase().includes(word.toLowerCase())
        ).slice(0, 10); // Limit to 10 suggestions
    }
    
    updateSuggestionBox() {
        this.suggestionBox.innerHTML = '';
        
        this.suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                ${index === this.currentSuggestion ? 'background-color: #007bff; color: white;' : ''}
            `;
            item.textContent = suggestion;
            
            item.addEventListener('click', () => {
                this.currentSuggestion = index;
                this.acceptSuggestion();
            });
            
            item.addEventListener('mouseenter', () => {
                this.currentSuggestion = index;
                this.updateSuggestionBox();
            });
            
            this.suggestionBox.appendChild(item);
        });
        
        this.suggestionBox.style.display = 'block';
    }
    
    positionSuggestionBox(cursorPos) {
        const textareaRect = this.textarea.getBoundingClientRect();
        const textBeforeCursor = this.textarea.value.substring(0, cursorPos);
        const lines = textBeforeCursor.split('\n');
        const currentLine = lines[lines.length - 1];
        
        // Calculate position based on cursor position
        const lineHeight = 20; // Approximate line height
        const charWidth = 8; // Approximate character width
        
        const top = textareaRect.top + (lines.length - 1) * lineHeight;
        const left = textareaRect.left + currentLine.length * charWidth;
        
        this.suggestionBox.style.top = `${top}px`;
        this.suggestionBox.style.left = `${left}px`;
    }
    
    acceptSuggestion() {
        if (!this.isVisible || this.suggestions.length === 0) return;
        
        const suggestion = this.suggestions[this.currentSuggestion];
        const cursorPos = this.textarea.selectionStart;
        const text = this.textarea.value;
        const currentWord = this.getCurrentWord(text, cursorPos);
        
        const beforeWord = text.substring(0, cursorPos - currentWord.length);
        const afterWord = text.substring(cursorPos);
        
        this.textarea.value = beforeWord + suggestion + afterWord;
        this.textarea.selectionStart = cursorPos - currentWord.length + suggestion.length;
        this.textarea.selectionEnd = this.textarea.selectionStart;
        
        this.hideSuggestions();
        this.textarea.focus();
    }
    
    nextSuggestion() {
        this.currentSuggestion = (this.currentSuggestion + 1) % this.suggestions.length;
        this.updateSuggestionBox();
    }
    
    previousSuggestion() {
        this.currentSuggestion = this.currentSuggestion === 0 ? 
            this.suggestions.length - 1 : this.currentSuggestion - 1;
        this.updateSuggestionBox();
    }
    
    hideSuggestions() {
        this.isVisible = false;
        this.suggestionBox.style.display = 'none';
    }
}

// Initialize auto-complete for PHP textareas
document.addEventListener('DOMContentLoaded', function() {
    const phpTextareas = document.querySelectorAll('textarea[data-php-autocomplete="true"], textarea.php-editor');
    
    phpTextareas.forEach(textarea => {
        new PHPAutoComplete(textarea);
    });
    
    // Also initialize for dynamically created textareas
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const newTextareas = node.querySelectorAll ? 
                        node.querySelectorAll('textarea[data-php-autocomplete="true"], textarea.php-editor') : [];
                    newTextareas.forEach(textarea => {
                        new PHPAutoComplete(textarea);
                    });
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Export for use in other scripts
window.PHPAutoComplete = PHPAutoComplete; 