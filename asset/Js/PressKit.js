var PressKit = PressKit || {};

PressKit.Class = Class.extend({});

PressKit.Registry = PressKit.Class.extend({
	tagObjects: {}
	, selectorClasses: {}
	, objectCount: 0
	, bindingAttr: 'data-PressKit-Object'
	, init: function()
	{
		
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
					classObj = this.selectorClasses[selector]
					break;
				}
			}

			if(classObj)
			{
				widgetObj = new classObj(tag, event);
				this.register(widgetObj, tag);
			}
		}

		return widgetObj;
	}
	, autoRegisterTags: function(classes)
	{
		for(var selector in classes)
		{
			var classObj = classes[selector];
			var handlers = classes[selector].prototype.events;
			
			this.selectorClasses[selector] = classObj;

			var _this = this;

			for(var eventName in handlers)
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

		for(var selector in classes)
		{
			$(selector).each(function()
			{
				//console.log(this);
				PressKit.getRegistry().getObjectForTag($(this));
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
	, getSubwidget: function(name)
	{
		var subTag = $(this.tag).find(this.subWidgetSelectors[name]);

		return PressKit.getRegistry().getObjectForTag(subTag);
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
			click: function(event)
			{
				console.log('HEY!');
			}
			, keyup: 'searchSubWidgetKeyupHandler'
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
				event.preventDefault();
			}
		}
	}
	, onSubWidgetLink: function(subWidgetName, subWidget)
	{
		this.searchEndpoint = this.tag.attr('data-PressKit-Search-Endpoint');
		this.titlePoint = this.tag.attr('data-PressKit-Title-Point');
		this.previewImagePoint = this.tag.attr('data-PressKit-Preview-Image-Point');
		
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
					var linkTag = _this.renderSearchResults(results.shift());
					_this.setIndicator(linkTag, true);
				}
			});
		}
	}
	, searchResultSubWidgetClickHandler: function(event, linkWidget)
	{
		event.preventDefault();

		var idWidget = this.subWidgets.id;
		var classWidget = this.subWidgets.class;
		var searchWidget = this.subWidgets.search;
		
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

		this.setIndicator(linkWidget.tag)
	}
	, setIndicator: function(html, immediate)
	{
		var indicatorWidget = this.subWidgets.indicator;
		console.log(this.objectId, this.subWidgets);
		var container = indicatorWidget.tag.children('div.selection');
		console.log(indicatorWidget);
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

		console.log(container, indicatorWidget);
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

				$.ajax({
					'url': _this.searchEndpoint
					, 'data': {'keyword': searchTerm, 'api': 'json'}
					, 'method': 'GET'
					, 'dataType': 'json'
					, 'success': function(results)
					{
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

						// console.log(results);

						_this.searchResults.html(' ');

						if(!results.length)
						{
							_this.searchResults.html('No results found.');

						}

						for(var i in results)
						{
							_this.searchResults.append(
								_this.renderSearchResults(results[i])
							);
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
		var option = $('<a class = "PressKitAjaxSearchResult">')
			.attr('href', '/images/' + result.publicId)
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

		if(tag.attr('data-multi'))
		{
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

		this.subWidgets.subFields.map(function(subWidget)
		{
			console.log(subWidget);

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

		var index = this.subWidgets.subFields.length - 1;

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
$(function()
{
	PressKit.getRegistry().autoRegisterTags({
		'a': PressKit.LinkWidgetModel
		, 'input': PressKit.InputWidgetModel
		, '[data-presskit-widget="ModelSearch"]': PressKit.ModelSearchWidget
		, 'fieldset': PressKit.FieldSetWidget
	});

	//PressKit.ModelSearchWidget();
});

/*

var PressKit = PressKit || {};

PressKit.Class = Class.extend({});

PressKit.Registry = PressKit.Class.extend({
	tagObjects: {}
	, selectorClasses: {}
	, objectCount: -1
	, bindingAttr: 'data-PressKit-Object'
	, init: function()
	{
		
	}
	, register: function(object, tag)
	{
		this.objectCount++
		this.tagObjects[this.objectCount] = object;
		object.objectId = this.objectCount;
		tag.attr(this.bindingAttr, this.objectCount);

		return this.objectCount;
	}
	, getObjectForTag: function(tag, classObj)
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
			if(classObj)
			{
				widgetObj = new classObj(tag);
				this.register(widgetObj, tag);
			}
		}

		return widgetObj;
	}
	, linkWidgets: function(newWidgets)
	{
		for(var i in newWidgets)
		{
			var superWidgetTag;
			var curTag = newWidgets[i].tag;

			while((superWidgetTag = curTag.parent()) && superWidgetTag.prop('tagName'))
			{
				var superWidget = this.getObjectForTag(
					superWidgetTag
				);

				if(superWidget)
				{
					if(superWidget.subWidgetInit(newWidgets[i]))
					{
						break;
					}
				}

				curTag = superWidgetTag;
			}
		}
	}
	, registerTags: function(classes)
	{
		var _this = this;

		$(function()
		{
			var newWidgets = [];

			for(var selector in classes)
			{
				$(document).bind('DOMNodeInserted', (function(selector, classes, _this){
					var insertInvoluter = function(event, tag, _selector)
					{
						var insertedTag = tag || $(event.target);

						if(insertedTag.is(_selector))
						{
							selector = _selector;
						}
						
						if(!insertedTag.is(selector))
						{
							return;
						}

						var newWidgets = [];

						var widget = _this.getObjectForTag(
							insertedTag, classes[selector]
						);

						newWidgets.push(widget);

						insertedTag.children('*').map(function()
						{
							var tag = $(this);

							console.log(tag);

							for(var _selector in classes)
							{
								if(tag.is(_selector))
								{
									insertInvoluter(null, tag, _selector);
									break;
								}
							}
						});

						if(!widget)
						{
							return;
						}

						_this.linkWidgets([widget]);

						console.log('inserted new ' + selector, widget);
					};

					return insertInvoluter;
				})(selector, classes, _this));

				console.log(selector);
				
				$(selector).map(function()
				{
					console.log(selector);

					var widget = _this.getObjectForTag(
						$(this), classes[selector]
					);

					if(widget)
					{
						newWidgets.push(widget);
					}
				});
			}

			_this.linkWidgets(newWidgets);
		});
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

PressKit.Widget = PressKit.Class.extend({
	className: 'Widget'
	, tag: null
	, objectId: null
	, className: 'Widget'
	, superWidget: null
	, timers: {}
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
	, init: function(tag, event)
	{
		this.tag = tag;
		this.events = $.extend(true, {}, this.events);
		this.subWidgets = $.extend(true, {}, this.subWidgets);
		this.timers = $.extend(true, {}, this.timers);
		this.events = this.mergeEvents(this);

		for(var i in this.events)
		{
			if(1||this.events[i].length)
			{
				var _this = this;

				this.tag.on(i, function(event)
				{
					_this.callHandlers(_this.events[event.type], [event]);
				});
			}
		}
	}
	, mergeEvents: function(level)
	{
		var mergedEvents = {};

		var parentEvents = level.__proto__.__proto__.events;
		var childEvents = level.events;

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

				subWidget.superWidget = this;
				subWidget.tag.attr('data-PressKit-Super', this.objectId);

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
					console.log(
						'Checking ' + this.className
						+ ' for subWidgetHandlers'
					);

					for(var eventName in this.subWidgetHandlers[widgetName])
					{
						var subHandler = this.subWidgetHandlers[widgetName][eventName];

						console.log(
							'Binding to ' + eventName
							+ ' on '
							+ subWidget.className
							+ ' from '
							+ this.className
						);

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
					}
				}

				break;
			}
		}

		return found;
	}
	, onSubWidgetLink: function(subWidgetName, subWidget)
	{

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
		else if(typeof handler === 'object' && handler.length)
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
	, set: function(value)
	{
		this.tag.html(value);
	}
	, value: function()
	{
		return this.tag.html();
	}
});

PressKit.AnchorWidget = PressKit.Widget.extend({
	className: 'AnchorWidget'
	, events: {
		click: 'clickHandler'
	}
	, clickHandler: function(event)
	{
	}
	, mouseenterHandler: function(event)
	{
		console.log('MouseEnter on ' + this.className + ' #' + this.objectId);
	}
	, mouseleaveHandler: function() {
		console.log('MouseLeave on ' + this.className + ' #' + this.objectId);
	}
});

PressKit.FieldsetWidget = PressKit.Widget.extend({
	className: 'FieldsetWidget'
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
		console.log(this.subWidgets.subFields);

		if(tag.attr('data-multi'))
		{
			this.appendAddButton();
		}
	}
	, removeAddButton: function()
	{
		this.tag.children(this.subWidgetSelectors['add']).remove();
	}
	, appendAddButton: function()
	{
		var addButton = $('<input type = "button" value = "Add" />');
		addButton.attr('data-button', this.addButtonAttr);
		this.tag.append(addButton);
	}
	, addClickHandler: function()
	{
		var protoTag = null;

		console.log(this.subWidgets.subFields);

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

		newTag.find('*').removeAttr('data-PressKit-Object');
		newTag.removeAttr('data-PressKit-Object');

		var index = 0;

		while(true)
		{
			var newName = newTag.attr('name').replace(
				subTagRegex
				, parentName + '[' + index + ']'
			);

			if(this.tag.find('[name="' + newName + '"]').length)
			{
				index++;
			}
			else
			{
				break;
			}
		}

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

		PressKit.getRegistry().getObjectForTag(newTag);

		this.tag.append(newTag);

		this.removeAddButton();
		this.appendAddButton();
	}
	, removeClickHandler: function(event, widget)
	{
		console.log(this.subWidgets.subFields.splice(widget.tag.parent().index()+1, 1));
		widget.tag.parent().remove();
	}
});

PressKit.DocumentWidget = PressKit.Widget.extend({
	className: 'DocumentWidget'
});

PressKit.InputWidget = PressKit.Widget.extend({
	className: 'InputWidget'
	, events: {
		keyup:'keyupHandler'
		, click: 'clickHandler'
	}
	, keyupHandler: function(event)
	{
		console.log(this.className + ' ' + this.value());
	}
	, clickHandler: function(event)
	{
		console.log('FOCUS');
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

PressKit.ModelSearchWidget = PressKit.Widget.extend({
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
	, searchResults: null
	, searchEndpoint: null
	, titlePoint: null
	, previewImagePoint: null
	, subWidgetHandlers: {
		search: {
			click: function(event)
			{
				console.log('HEY!');
			}
			, keyup: 'searchSubWidgetKeyupHandler'
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
				event.preventDefault();
			}
		}
	}
	, onSubWidgetLink: function(subWidgetName, subWidget)
	{
		this.searchEndpoint = this.tag.attr('data-PressKit-Search-Endpoint');
		this.titlePoint = this.tag.attr('data-PressKit-Title-Point');
		this.previewImagePoint = this.tag.attr('data-PressKit-Preview-Image-Point');
		
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
					var linkTag = _this.renderSearchResults(results.shift());
					_this.setIndicator(linkTag, true);
				}
			});
		}
	}
	, searchResultSubWidgetClickHandler: function(event, linkWidget)
	{
		event.preventDefault();

		var idWidget = this.subWidgets.id;
		var classWidget = this.subWidgets.class;
		var searchWidget = this.subWidgets.search;
		
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

		this.setIndicator(linkWidget.tag)
	}
	, setIndicator: function(html, immediate)
	{
		var indicatorWidget = this.subWidgets.indicator;
		console.log(this.objectId, this.subWidgets);
		var container = indicatorWidget.tag.children('div.selection');
		console.log(indicatorWidget);
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

		console.log(container, indicatorWidget);
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

				$.ajax({
					'url': _this.searchEndpoint
					, 'data': {'title': searchTerm, 'api': 'json'}
					, 'method': 'GET'
					, 'dataType': 'json'
					, 'success': function(results)
					{
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

						// console.log(results);

						_this.searchResults.html(' ');

						if(!results.length)
						{
							_this.searchResults.html('No results found.');

						}

						for(var i in results)
						{
							_this.searchResults.append(
								_this.renderSearchResults(results[i])
							);
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
		var option = $('<a class = "PressKitAjaxSearchResult">')
			.attr('href', '/images/' + result.publicId)
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

PressKit.getRegistry().registerTags({
	'body': PressKit.DocumentWidget
	, '[data-presskit-widget="ModelSearch"]': PressKit.ModelSearchWidget
	, 'a': PressKit.AnchorWidget
	, 'fieldset': PressKit.FieldsetWidget
	, 'input': PressKit.InputWidget
});

*/
// <fieldset>whut<a>yup</a><fieldset>