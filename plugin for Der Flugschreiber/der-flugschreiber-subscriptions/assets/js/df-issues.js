(function () {
    function serializeFormData(data) {
        var formData = new window.FormData();

        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        return formData;
    }

    function initDropdown(dropdown, onChange) {
        var button = dropdown.querySelector('.df-issues__dropdown-button');
        var menu = dropdown.querySelector('.df-issues__dropdown-menu');
        var label = dropdown.querySelector('[data-dropdown-value-label]');
        var options = Array.prototype.slice.call(dropdown.querySelectorAll('.df-issues__dropdown-option'));

        function close() {
            dropdown.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        function open() {
            dropdown.classList.add('is-open');
            button.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
        }

        button.addEventListener('click', function () {
            if (dropdown.classList.contains('is-open')) {
                close();
            } else {
                Array.prototype.slice.call(document.querySelectorAll('.df-issues__dropdown.is-open')).forEach(function (item) {
                    if (item !== dropdown) {
                        item.classList.remove('is-open');
                        item.querySelector('.df-issues__dropdown-button').setAttribute('aria-expanded', 'false');
                        item.querySelector('.df-issues__dropdown-menu').hidden = true;
                    }
                });
                open();
            }
        });

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                var value = option.getAttribute('data-value') || '';
                var optionLabel = option.getAttribute('data-label') || option.textContent.trim();

                dropdown.setAttribute('data-value', value);
                label.textContent = optionLabel;
                options.forEach(function (item) {
                    var active = item === option;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                close();
                onChange(dropdown.getAttribute('data-dropdown'), value);
            });
        });

        document.addEventListener('click', function (event) {
            if (!dropdown.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
    }

    function initIssuesBlock(block) {
        var typeButtons = Array.prototype.slice.call(block.querySelectorAll('[data-filter-type]'));
        var dropdowns = Array.prototype.slice.call(block.querySelectorAll('[data-dropdown]'));
        var topicChips = Array.prototype.slice.call(block.querySelectorAll('[data-topic-chip]'));
        var grid = block.querySelector('.df-issues__grid');
        var moreButton = block.querySelector('.df-issues__more');
        var empty = block.querySelector('.df-issues__empty');
        var initial = parseInt(block.getAttribute('data-initial'), 10) || 12;
        var step = parseInt(block.getAttribute('data-step'), 10) || 4;
        var activeType = 'paid';
        var currentPage = 1;
        var requestController = null;
        var requestSequence = 0;

        function getDropdownValue(key) {
            var dropdown = block.querySelector('[data-dropdown="' + key + '"]');
            return dropdown ? dropdown.getAttribute('data-value') || '' : '';
        }

        function setDropdownValue(key, value) {
            var dropdown = block.querySelector('[data-dropdown="' + key + '"]');

            if (!dropdown) {
                return;
            }

            var option = dropdown.querySelector('.df-issues__dropdown-option[data-value="' + value + '"]');

            if (option) {
                option.click();
            }
        }

        function setLoading(loading) {
            block.classList.toggle('is-loading', loading);

            if (moreButton) {
                moreButton.disabled = loading;
            }
        }

        function getState(page, append) {
            return {
                action: 'df_filter_issues',
                nonce: window.DFIssues ? window.DFIssues.nonce : '',
                type: activeType,
                year: getDropdownValue('year'),
                topic: getDropdownValue('topic'),
                sort: getDropdownValue('sort') || 'newest',
                limit: append ? step : initial,
                page: page,
                offset: append ? initial + ((page - 2) * step) : 0,
                append: append ? '1' : ''
            };
        }

        function requestIssues(page, append) {
            if (!window.DFIssues || !window.DFIssues.ajaxUrl) {
                return;
            }

            if (requestController) {
                requestController.abort();
            }

            requestController = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            var requestId = ++requestSequence;
            setLoading(true);

            window.fetch(window.DFIssues.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: serializeFormData(getState(page, append)),
                signal: requestController ? requestController.signal : undefined
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.success) {
                        throw new Error('Invalid response');
                    }

                    if (requestId !== requestSequence) {
                        return;
                    }

                    currentPage = page;

                    if (append) {
                        grid.insertAdjacentHTML('beforeend', response.data.html || '');
                    } else {
                        grid.innerHTML = response.data.html || '';
                    }

                    if (empty) {
                        empty.hidden = !!response.data.found;
                    }

                    if (moreButton) {
                        moreButton.hidden = !response.data.hasMore;
                    }
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    if (empty) {
                        empty.hidden = false;
                        empty.textContent = window.DFIssues && window.DFIssues.errorMessage ? window.DFIssues.errorMessage : 'Die Ausgaben konnten nicht geladen werden.';
                    }
                })
                .finally(function () {
                    if (requestId === requestSequence) {
                        setLoading(false);
                    }
                });
        }

        function refresh() {
            currentPage = 1;
            requestIssues(1, false);
        }

        dropdowns.forEach(function (dropdown) {
            initDropdown(dropdown, function (key) {
                if (key === 'topic') {
                    topicChips.forEach(function (item) {
                        item.classList.toggle('is-active', item.getAttribute('data-topic-chip') === getDropdownValue('topic'));
                    });
                }

                refresh();
            });
        });

        typeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                activeType = button.getAttribute('data-filter-type') || 'paid';
                typeButtons.forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });
                refresh();
            });
        });

        topicChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var topic = chip.getAttribute('data-topic-chip') || '';
                var nextValue = getDropdownValue('topic') === topic ? '' : topic;
                setDropdownValue('topic', nextValue);
            });
        });

        if (moreButton) {
            moreButton.addEventListener('click', function () {
                requestIssues(currentPage + 1, true);
            });
        }
    }

    function initArticlesBlock(block) {
        var dropdowns = Array.prototype.slice.call(block.querySelectorAll('[data-dropdown]'));
        var topicChips = Array.prototype.slice.call(block.querySelectorAll('[data-topic-chip]'));
        var list = block.querySelector('.df-articles__list');
        var moreButton = block.querySelector('.df-articles__more');
        var empty = block.querySelector('.df-articles__empty');
        var initial = parseInt(block.getAttribute('data-initial'), 10) || 5;
        var step = parseInt(block.getAttribute('data-step'), 10) || 5;
        var currentPage = 1;
        var requestController = null;
        var requestSequence = 0;

        function getDropdownValue(key) {
            var dropdown = block.querySelector('[data-dropdown="' + key + '"]');
            return dropdown ? dropdown.getAttribute('data-value') || '' : '';
        }

        function setDropdownValue(key, value) {
            var dropdown = block.querySelector('[data-dropdown="' + key + '"]');

            if (!dropdown) {
                return;
            }

            var option = dropdown.querySelector('.df-issues__dropdown-option[data-value="' + value + '"]');

            if (option) {
                option.click();
            }
        }

        function setLoading(loading) {
            block.classList.toggle('is-loading', loading);

            if (moreButton) {
                moreButton.disabled = loading;
            }
        }

        function getState(page, append) {
            return {
                action: block.getAttribute('data-action') || 'df_filter_articles',
                nonce: window.DFIssues ? window.DFIssues.nonce : '',
                magazine: getDropdownValue('magazine'),
                topic: getDropdownValue('topic'),
                sort: getDropdownValue('sort') || 'newest',
                layout: block.getAttribute('data-layout') || 'list',
                limit: append ? step : initial,
                page: page,
                offset: append ? initial + ((page - 2) * step) : 0,
                append: append ? '1' : ''
            };
        }

        function requestArticles(page, append) {
            if (!window.DFIssues || !window.DFIssues.ajaxUrl) {
                return;
            }

            if (requestController) {
                requestController.abort();
            }

            requestController = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            var requestId = ++requestSequence;
            setLoading(true);

            window.fetch(window.DFIssues.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: serializeFormData(getState(page, append)),
                signal: requestController ? requestController.signal : undefined
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.success) {
                        throw new Error('Invalid response');
                    }

                    if (requestId !== requestSequence) {
                        return;
                    }

                    currentPage = page;

                    if (append) {
                        list.insertAdjacentHTML('beforeend', response.data.html || '');
                    } else {
                        list.innerHTML = response.data.html || '';
                    }

                    if (empty) {
                        empty.hidden = !!response.data.found;
                    }

                    if (moreButton) {
                        moreButton.hidden = !response.data.hasMore;
                    }
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    if (empty) {
                        empty.hidden = false;
                        empty.textContent = window.DFIssues && window.DFIssues.errorMessage ? window.DFIssues.errorMessage : 'Die Artikel konnten nicht geladen werden.';
                    }
                })
                .finally(function () {
                    if (requestId === requestSequence) {
                        setLoading(false);
                    }
                });
        }

        function refresh() {
            currentPage = 1;
            requestArticles(1, false);
        }

        dropdowns.forEach(function (dropdown) {
            initDropdown(dropdown, function (key) {
                if (key === 'topic') {
                    topicChips.forEach(function (item) {
                        item.classList.toggle('is-active', item.getAttribute('data-topic-chip') === getDropdownValue('topic'));
                    });
                }

                refresh();
            });
        });

        topicChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var topic = chip.getAttribute('data-topic-chip') || '';
                var nextValue = getDropdownValue('topic') === topic ? '' : topic;
                setDropdownValue('topic', nextValue);
            });
        });

        if (moreButton) {
            moreButton.addEventListener('click', function () {
                requestArticles(currentPage + 1, true);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.slice.call(document.querySelectorAll('.df-issues')).forEach(initIssuesBlock);
        Array.prototype.slice.call(document.querySelectorAll('.df-articles')).forEach(initArticlesBlock);
    });
})();
