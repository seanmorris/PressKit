/* Boilerplate */
var PressKit = PressKit || {};
PressKit.Class = Class.extend({});
PressKit.Registry = PressKit.Class.extend({
	tagObjects: {}
	, selectorClasses: {}
	, objectCount: 0
	, bindingAttr: 'data-PressKit-Object'
	, observer: null
	, init: function()
	{
		this.observer = new MutationObserver(function(mutations){
			mutations.forEach(function (mutation) {
				var entry = {
					mutation: mutation,
					el: mutation.target,
					value: mutation.target.textContent,
					oldValue: mutation.oldValue
				};
				console.log('Recording mutation:', mutation);
			});
		});

		this.observer.observe(document.body, {
			characterData: true 
		});
	}
	, register: function(object, tag)
	{
		this.tagObjects[this.objectCount] = object;

		object.objectId = this.objectCount;
		tag.attr(this.bindingAttr, this.objectCount);

		return this.objectCount++;
	}
	, getObjectForTag: function(tag, event)
	{
		var objectId = tag.attr(this.bindingAttr);
		var widgetObj;
		var classObj;

		if(objectId || objectId === 0)
		{
			widgetObj = this.tagObjects[ objectId ];
		}
		else
		{
			for(var selector in this.selectorClasses)
			{
				if(tag.is(selector))
				{
					classObj = this.selectorClasses[selector];
					widgetObj = new classObj(tag, event);
					this.register(widgetObj, tag);
					break;
				}
			}
		}

		return widgetObj;
	}
	, registerClasses: function(classes)
	{
		for(var selector in classes)
		{
			if(!classes[selector])
			{
				continue;
			}

			var classObj = classes[selector];
			var handlers = classes[selector].prototype.events;

			this.selectorClasses[selector] = classObj;
		}
	}
	, start: function(classes)
	{
		for(var selector in this.selectorClasses)
		{
			var _this = this;

			for(var eventName in this.selectorClasses[selector].prototype.events)
			{
				$(document).on(eventName, selector, {}, (function(eventName)
				{
					return function(event)
					{
						var tag = $(this);

						if(widgetObj = _this.getObjectForTag(tag, event))
						{
							var handler = widgetObj.events[eventName];

							if(handler)
							{
								widgetObj.callHandlers(handler, [event]);
							}
						}
					}
				})(eventName));
			}
		}

		for(var selector in this.selectorClasses)
		{
			$(selector).map(function(index, tag)
			{
				PressKit.getRegistry().getObjectForTag($(tag));
			});
		}
	}
});
PressKit.getRegistry = function()
{
	if(!PressKit._registry)
	{
		PressKit._registry = new PressKit.Registry();
	}

	return PressKit._registry;
};
PressKit.register = function(classes) {
	PressKit.getRegistry().registerClasses(classes);
};
PressKit.start = function() {
	PressKit.getRegistry().start();
};
PressKit.WidgetModel = PressKit.Class.extend({
	name: 'WidgetModel'
	, registry: null
	, tag: null
	, events: {
		blur: []
		,  focus: []
		,  focusin: []
		,  focusout: []
		,  load: []
		,  resize: []
		,  scroll: []
		,  unload: []
		,  click: []
		,  dblclick: []
		,  mousedown: []
		,  mouseup: []
		,  mousemove: []
		,  mouseover: []
		,  mouseout: []
		,  mouseenter: []
		,  mouseleave: []
		,  change: []
		,  select: []
		,  submit: []
		,  keydown: []
		,  keypress: []
		,  keyup: []
		,  error: []
	}
	, superWidget: null
	, subWidgets: {}
	, subWidgetSelectors: {}
	, subWidgetHandlers: {}
	, objectId: null
	, init: function(tag, event)
	{
		this.events = $.extend({}, this.events);
		this.subWidgets = $.extend({}, this.subWidgets);
		this.subWidgetHandlers = $.extend({}, this.subWidgetHandlers);

		for(var widgetName in this.subWidgets)
		{
			if(Array.isArray(this.subWidgets[widgetName]))
			{
				this.subWidgets[widgetName] = [];
			}
		}

		this.tag = tag;
		var _this = this;
		this.events = this.mergeEvents(this);

		var parentTag = tag;

		while(parentTag.prop("tagName"))
		{
			parentTag = parentTag.parent();
			superWidget = PressKit.getRegistry().getObjectForTag(parentTag, event);

			if(superWidget)
			{
				if(superWidget.subWidgetInit(this, event))
				{
					break;
				}
			}
		}
	}
	, getSubwidget: function(name, index)
	{
		console.log(name);

		var subTag = $(this.tag).find(this.subWidgetSelectors[name]);

		var subWidgets = subTag.map(function(index, element){
			// console.log(element);
			return PressKit.getRegistry().getObjectForTag($(element))
		});

		if(typeof index !== 'undefined')
		{
			subTag = subTag[index];
		}
		else
		{
			index = 0;
		}

		return subWidgets[index];
	}
	, onSubWidgetLink: function(subWidgetName, subWidget) {}
	, subWidgetInit: function(subWidget, event)
	{
		var _this = this;

		var found = false;

		for(var widgetName in this.subWidgetSelectors)
		{
			if(subWidget.tag.is(this.subWidgetSelectors[widgetName]))
			{
				found = true;

				subWidget.superWidget = this;

				if(this.subWidgets[widgetName] && Array.isArray(this.subWidgets[widgetName]))
				{
					this.subWidgets[widgetName].push(subWidget);
					this.onSubWidgetLink(widgetName, subWidget);
				}
				else
				{
					this.subWidgets[widgetName] = subWidget;
					this.onSubWidgetLink(widgetName, subWidget);
				}

				if(this.subWidgetHandlers[widgetName])
				{
					for(var eventName in this.subWidgetHandlers[widgetName])
					{
						var subHandler = this.subWidgetHandlers[widgetName][eventName];

						subWidget.pushHandler(
							eventName
							, (function(subWidget, subHandler)
							{
								return function(event)
								{
									_this.callHandlers(subHandler, [event, subWidget]);
								}
							})(subWidget, subHandler)
						);

						if(event && $(event.target).is(subWidget.tag) && event.type == eventName)
						{
							// this.callHandlers(subHandler, [event, subWidget]);
						}
					}
				}

				break;
			}
		}

		return found;
	}
	, mergeEvents: function(level)
	{
		var mergedEvents = {};

		parentEvents = level.__proto__.__proto__.events;
		childEvents = level.events;

		for(var eventName in parentEvents)
		{
			if(!parentEvents[eventName] || !parentEvents[eventName].length)
			{
				continue;
			}

			mergedEvents[eventName] = [parentEvents[eventName]];
		}

		for(var eventName in childEvents)
		{
			if(!mergedEvents[eventName])
			{
				mergedEvents[eventName] = [];
			}

			if(!childEvents[eventName] || !childEvents[eventName].length)
			{
				continue;
			}

			mergedEvents[eventName].push(childEvents[eventName]);
		}

		if(level.__proto__.__proto__ && level.__proto__.__proto__.events)
		{
			var subMergedEvents = this.mergeEvents(level.__proto__.__proto__);
		}

		for(var eventName in subMergedEvents)
		{
			if(!mergedEvents[eventName])
			{
				mergedEvents[eventName] = [];
			}

			if(!subMergedEvents[eventName] || !subMergedEvents[eventName].length)
			{
				continue;
			}

			mergedEvents[eventName].push(subMergedEvents[eventName]);
		}

		return mergedEvents;
	}
	, callHandlers: function(handler, args)
	{
		args = args || [];

		if(typeof handler === 'function')
		{
			handler.apply(this, args);
		}
		else if(typeof handler === 'string')
		{
			handler = this[handler];
			if(handler)
			{
				handler.apply(this, args);
			}
		}
		else if(typeof handler === 'object')
		{
			for(var i in handler)
			{
				this.callHandlers(handler[i], args);
			}
		}
	}
	, resolveHandler: function(eventName, obj)
	{
		return this.events[eventName];
	}
	, pushHandler: function(event, handler)
	{
		if(typeof this.events[event] !== 'object')
		{
			if(this.events[event])
			{
				this.events[event] = [this.events[event]];
			}
			else
			{
				this.events[event] = [];
			}
		}

		this.events[event].push(handler);
	}
	, value: function()
	{
		return null;
	}
});

PressKit.WidgetModel.addEvents = function(events)
{
	PressKit.WidgetModel.prototype.events = Object.assign(
		PressKit.WidgetModel.prototype.events
		, events
	);
};

/* /Boilerplate */

/* Base Fields */
PressKit.InputWidgetModel = PressKit.WidgetModel.extend({
	name: 'InputWidgetModel'
	, events: {
		keyup:'keyupHandler'
		, click: 'clickHandler'
	}
	, keyupHandler: function(event)
	{
		// console.log(this.name);
	}
	, clickHandler: function(event)
	{
		// console.log('222');
	}
	, set: function(value)
	{
		this.tag.val(value);
	}
	, value: function()
	{
		return this.tag.val();
	}
});

PressKit.LinkWidgetModel = PressKit.WidgetModel.extend({
	name: 'LinkWidgetModel'
	, events: {
		mouseenter: ['mouseenterHandler', 'mouseenterHandler']
		, mouseleave: 'mouseleaveHandler'
		, click: 'clickHandler'
	}
	, clickHandler: function(event)
	{
	}
	, mouseenterHandler: function(event)
	{
		console.log('MouseEnter on ' + this.name + ' #' + this.objectId);
	}
	, mouseleaveHandler: function() {
		console.log('MouseLeave on ' + this.name + ' #' + this.objectId);
	}
});
/* /Base Fields */

/* Search Field */
PressKit.ModelSearchWidget = PressKit.WidgetModel.extend({
	className: 'ModelSearchWidget'
	, subWidgets: {
		search: null
		, id: null
		, results: []
		, indicator: null
	}
	, subWidgetSelectors: {
		search: 'input[data-presskit-field="search"]'
		, id: 'input[data-presskit-field="id"]'
		, class: 'input[data-presskit-field="class"]'
		, results: '.PressKitAjaxSearchResults > a'
		, indicator: '[data-presskit-field="indicator"]'
	}
	, timers: {}
	, searchResults: null
	, searchEndpoint: null
	, titlePoint: null
	, previewImagePoint: null
	, subWidgetHandlers: {
		search: {
			keyup: 'searchSubWidgetKeyupHandler'
			, click: 'searchSubWidgetKeyupHandler'
		}
		, results: {
			click: 'searchResultSubWidgetClickHandler'
			, mouseenter: function(event)
			{
				console.log('wat');
			}
		}
		, indicator: {
			click: function(event)
			{
				//event.preventDefault();
			}
		}
	}
	, onSubWidgetLink: function(subWidgetName, subWidget)
	{
		this.searchEndpoint = this.tag.attr('data-PressKit-Search-Endpoint');
		this.titlePoint = this.tag.attr('data-PressKit-Title-Point');
		this.previewImagePoint = this.tag.attr('data-PressKit-Preview-Image-Point');
		this.keywordField = this.tag.attr('data-PressKit-Keyword-Field');

		if(subWidgetName == 'id')
		{
			var modelId = subWidget.value();

			if(!modelId)
			{
				return;
			}

			var searchWidget = this.subWidgets.search;
			var indicatorWidget = this.subWidgets.indicator;

			var _this = this;

			$.ajax({
				'url': this.searchEndpoint
				, 'data': {'id': modelId, 'api': 'json'}
				, 'method': 'GET'
				, 'dataType': 'json'
				, 'success': function(results)
				{
					results = results.body;
					var linkTag = _this.renderSearchResults(results.shift());
					_this.setIndicator(linkTag, true);
				}
			});
		}
	}
	, searchResultSubWidgetClickHandler: function(event, linkWidget)
	{
		if(!linkWidget.tag.attr('data-PressKit-Indicator-Link'))
		{
			event.preventDefault();
		}

		console.log(this.subWidgets);

		var idWidget = this.getSubwidget('id');
		var classWidget = this.getSubwidget('class');
		var searchWidget = this.getSubwidget('search');

		var modelId = linkWidget.tag.attr('data-PressKit-id');
		var modelClass = linkWidget.tag.attr('data-PressKit-class');

		if(modelId)
		{
			idWidget.set(modelId);
		}
		else if(modelClass)
		{
			idWidget.set(null);
			classWidget.set(modelClass);
		}

		if(this.searchResults)
		{
			this.searchResults.slideUp();
			this.searchResults.html(' ');
		}

		linkWidget.tag.attr('data-PressKit-Indicator-Link', true);

		this.setIndicator(linkWidget.tag)
	}
	, setIndicator: function(html, immediate)
	{
		var indicatorWidget = this.getSubwidget('indicator');
		// console.log(this.objectId, this.subWidgets);
		var container = indicatorWidget.tag.children('div.selection');
		// console.log(indicatorWidget);
		container.html(' ');
		container.append(html);

		if(immediate)
		{
			indicatorWidget.tag.show();
		}
		else
		{
			indicatorWidget.tag.slideDown();
		}

		// console.log(container, indicatorWidget);
	}
	, searchSubWidgetKeyupHandler: function(event, subWidget)
	{
		var _this = this;

		console.log('lel ' + subWidget.value());

		clearTimeout(this.timers['search']);

		this.timers['search'] = setTimeout(
			function()
			{
				var searchTerm = subWidget.value();

				if(!_this.searchEndpoint)
				{
					return;
				}

				if(searchTerm.length < 3)
				{
					if(_this.searchResults)
					{
						_this.searchResults.slideUp();
					}
					return;
				}

				var searchData = {'api': 'json'};

				searchData[_this.keywordField] = searchTerm;

				$.ajax({
					'url': _this.searchEndpoint
					, 'data': searchData
					, 'method': 'GET'
					, 'dataType': 'json'
					, 'success': function(results)
					{
						results = results.body;
						if(!_this.searchResults)
						{
							var top = subWidget.tag.offset().top
								+ subWidget.tag.outerHeight()
							;

							_this.searchResults = $('<div class = "PressKitAjaxSearchResults">');
							_this.searchResults.css({
								width: subWidget.tag.outerWidth()
								, display: 'none'
							});

							subWidget.tag.after(_this.searchResults);
						}

						console.log(results.length);

						_this.searchResults.html(' ');

						var resCount = 0;

						for(var i in results)
						{
							resCount++;
							_this.searchResults.append(
								_this.renderSearchResults(results[i])
							);
						}

						if(!resCount)
						{
							_this.searchResults.html('No results found.');
						}

						_this.searchResults.slideDown();
					}
				});
			}
			, 350
		);
	}
	, renderSearchResults: function(result)
	{
		if(!result)
		{
			return;
		}
		var option = $('<a class = "PressKitAjaxSearchResult">')
			.attr('href', this.searchEndpoint + '/' + (result.publicId || result.id))
			.attr('target', '_blank')
			.css({'display':'block'})
		;

		if(result.id)
		{
			option.attr('data-PressKit-id', result.id);
		}

		option.attr('data-PressKit-class', result.class);

		if(result[this.previewImagePoint])
		{
			option.append($('<img>')
				.attr('class', 'preview')
				.attr('src', result[this.previewImagePoint]))
			;
		}

		if(result[this.titlePoint])
		{
			option.append(result[this.titlePoint]);
		}

		return option;
	}
});

/* /Search Field */

/* Multi Field */
PressKit.FieldSetWidget = PressKit.WidgetModel.extend({
	name: 'FieldSetWidget'
	, subWidgets: {
		add: null
		, remove: []
		, subFields: []
	}
	, addButtonAttr: 'PressKit.FieldSetWidget.add'
	, removeButtonAttr: 'PressKit.FieldSetWidget.remove'
	, subWidgetSelectors: {
		add: '[data-button="PressKit.FieldSetWidget.add"]'
		, remove: '[data-button="PressKit.FieldSetWidget.remove"]'
		, subFields: '[data-presskit-widget="ModelSearch"]'
	}
	, subWidgetHandlers: {
		add: {
			click: 'addClickHandler'
		}
		, remove: {
			click: 'removeClickHandler'
		}
	}
	, init: function(tag, event)
	{
		this._super(tag, event);
		if(this.tag.attr('data-multi'))
		{
			console.log('Add Button for #'+this.objectId);

			this.removeAddButton();
			this.appendAddButton();
		}
	}
	, removeAddButton: function()
	{
		this.tag.children(this.subWidgetSelectors['add']).remove();
	}
	, appendAddButton: function()
	{
		var addButton = $('<input type = "button" value = "add" />');
		addButton.attr('data-button', this.addButtonAttr);
		this.tag.append(addButton);
	}
	, addClickHandler: function()
	{
		var protoTag = null;

		this.getSubwidget('subFields');

		this.subWidgets.subFields.map(function(subWidget)
		{
			var name = subWidget.tag.attr('name');

			if(name.match(/^\w+\[-1\]/))
			{
				protoTag = subWidget.tag;
			}
		});

		var newTag = protoTag.clone();

		var parentName = this.tag.attr('name');
		var subTagPrefix = parentName + '[-1]';
		var subTagRegex = '^' + subTagPrefix;

		var subTagPrefixSelector = '[name^="' + subTagPrefix + '"]';
		var subLabelPrefixSelector = '[for^="' + subTagPrefix + '"]';

		subTagRegex = subTagRegex.replace('[', '\\[');
		subTagRegex = subTagRegex.replace(']', '\\]');

		subTagRegex = RegExp(subTagRegex);

		newTag.removeAttr('disabled');

		var newSubTags = newTag.find('*')

		newSubTags.removeAttr('data-PressKit-Object');

		newTag.removeAttr('data-PressKit-Object');

		var index = this.subWidgets.subFields.length;

		var newName = parentName + '[' + index + ']';

		newTag.attr('name', newName);

		newTag.find(subTagPrefixSelector).map(function(){
			var newName = $(this).attr('name').replace(
				subTagRegex
				, parentName + '[' + index + ']'
			);

			$(this).attr('name', newName);
		});

		newTag.find(subLabelPrefixSelector).map(function(){
			var newName = $(this).attr('for').replace(
				subTagRegex
				, parentName + '[' + index + ']'
			);

			$(this).attr('for', newName);
		});

		console.log(newTag);

		this.tag.append(newTag);

		newSubTags.map(function(){
			PressKit.getRegistry().getObjectForTag($(this));
		});

		this.removeAddButton();
		this.appendAddButton();
	}
	, removeClickHandler: function(event, widget)
	{
		console.log(this.subWidgets.subFields.splice(widget.tag.parent().index()+1, 1));
		widget.tag.parent().remove();
	}
});

/* /Multi Field */
/*
PressKit.TerminalWidget = PressKit.WidgetModel.extend({
	name: 'Terminal'
	, terminalTag: null
	, terminalTagInner: null
	, init: function(tag, event)
	{
		this._super(tag, event);
		this.terminalTag = $('<div class = "pressKit-terminal"></div>');
		this.terminalTagInner = $('<div class ="pressKit-terminal-inner"></div>');

		this.terminalTag.append(this.terminalTagInner);
		tag.append(this.terminalTag);

		this.terminalTag.css({
			position: 'absolute',
			top: '0px',
			left: '0px',
			width: '100%',
			height: '100%',
			"z-index": 999999999,
			color: '#FFF',
			background: 'rgba(0,0,0,0.5)',
			"font-family": 'monospace',
		});

		this.terminalTagInner.css({
			'word-wrap': 'break-word'
		});

		console.log( this.getTerminalWidth() );

		$(window).resize(function(){
			console.log( this.getTerminalWidth() );
		});
	}
	, events: {
		click: 'clickHandler'
	}
	, getTerminalWidth: function()
	{
		this.terminalTagInner.html(this.terminalTagInner.html() + '!');

		var height = this.terminalTagInner.height();
		this.terminalTagInner.html('');

		var newHeight;
		var i = 0;
		while(++i < 80*1000)
		{
			this.terminalTagInner.html(this.terminalTagInner.html() + '!');
			newHeight = this.terminalTagInner.height();

			if(newHeight !== height)
			{
				return i;
			}
		}
	}
	, clickHandler: function(event)
	{
	}
});
*/
var ob = function(i) { return PressKit._registry.tagObjects[i] };
$(function()
{
	PressKit.getRegistry().registerClasses({
		'a': PressKit.LinkWidgetModel
		, 'input': PressKit.InputWidgetModel
		, '[data-presskit-widget="ModelSearch"]': PressKit.ModelSearchWidget
		, 'fieldset': PressKit.FieldSetWidget
		//, 'body': PressKit.TerminalWidget
	});
});