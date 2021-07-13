function Kyte(url, accessKey, identifier, account_number, adminRoleID = 1) {
	this.url = url;
	this.access_key = accessKey;
	this.identifier = identifier;
	this.account_number = account_number;
	// store non-handoff api keys
	this.initial_access_key = accessKey;
	this.initial_identifier = identifier;
	this.initial_account_number = account_number;

	this.txToken;
	this.sessionToken;
	this.adminRole = adminRoleID;
	this.dateFormat = 'mm/dd/yy';
}

Kyte.prototype.init = function() {
	this.access_key = (this.getCookie('kyte_pub') ? this.getCookie('kyte_pub') : this.access_key);
	this.identifier = (this.getCookie('kyte_iden') ? this.getCookie('kyte_iden') : this.identifier);
	this.account_number = (this.getCookie('kyte_num') ? this.getCookie('kyte_num') : this.account_number);

	// get txToken and session tokens from cookie if they exist (i.e. user session exists)
	this.txToken = (this.getCookie('txToken') ? this.getCookie('txToken') : 0);
	this.sessionToken = (this.getCookie('sessionToken') ? this.getCookie('sessionToken') : 0);
};

/* API Version
 *
 * Use sign() to obtain authorization to transact and
 * send serialized form data accompanied with signature data
 *
 */
Kyte.prototype.version = function(callback, error = null) {
	var obj = this;

	$.ajax({
		method: "GET",
		crossDomain: true,
		dataType: "json",
		url: obj.url,
		success: function(response){
			if (typeof callback === "function") {
				  callback(response.engine_version, response.framework_version);
			  } else {
				  console.log("Engine: "+response.engine_version+"; Framework: "+response.framework_version);
			  }
		},
		error: function(response) {
			if (typeof error === "function") {
				  error(response.responseJSON.error);
			  } else {
				  console.log(response.responseJSON.error);
				alert(response.responseJSON.error);
			}
		}
	});
};

/* API Signature Request
 *
 * Pass identifying information and public key to backend
 * requesting an authorization signature to transact
 *
 */
Kyte.prototype.sign = function(callback, error = null) {
	var d = new Date();
	var obj = this;

	$.ajax({
	    method: "POST",
	    crossDomain: true,
	    dataType: "json",
		url: obj.url,
		data: 'key='+obj.access_key+'&identifier='+obj.identifier+'&token='+obj.txToken+'&time='+d.toUTCString(),
	    success: function(response){
	      	if (typeof callback === "function") {
	      		callback(response, d);
	      	} else {
	      		console.log(response);
	      	}
	    },
	    error: function(response) {
	      	if (typeof error === "function") {
	      		error(response);
	      	} else {
	      		console.log(response);
		        alert(response);
		    }
	    }
    });
};

/* Send Data to Backend by Specified Method
 *
 * Use sign() to obtain authorization to transact and
 * send data accompanied with signature data
 *
 */
Kyte.prototype.sendData = function(method, model, field = null, value = null, data = null, formdata = null, callback, error = null) {
	var obj = this;
	var token = (obj.getCookie('kyte-token') ? obj.getCookie('kyte-token') : '1');

	this.sign(
		function(retval, time) {
		// /{signature}/{identity}/{model}/{field}/{value}
		let identity = encodeURIComponent(btoa(obj.access_key+'%'+obj.sessionToken+'%'+time.toUTCString()+'%'+obj.account_number));
		var apiURL = obj.url+'/'+retval.signature+'/'+identity+'/'+model;
		if (field) {
			apiURL += '/'+field;
		}
		if (value) {
			apiURL += '/'+value;
		}

		var encdata = '';

		if (data) {
			encdata += $.param(data);
		}

		if (formdata) {
			if (encdata) encdata += '&';
			encdata += formdata;
		}

		$.ajax({
			method: method,
			crossDomain: true,
			dataType: "json",
			url: apiURL,
			data: encdata,
			success: function(response) {
				obj.txToken = response.token;
				obj.sessionToken = response.session;
				if (response.kyte_pub && response.kyte_iden && response.kyte_num) {
					obj.access_key = response.kyte_pub;
					obj.identifier = response.kyte_iden;
					obj.account_number = response.kyte_num;
					obj.setCookie('kyte_pub', obj.access_key, 60);
					obj.setCookie('kyte_iden', obj.identifier, 60);
					obj.setCookie('kyte_num', obj.account_number, 60);
				} else {
					// destroy api handoff cookies
					obj.setCookie('kyte_pub', '', -1);
					obj.setCookie('kyte_num', '', -1);
					obj.setCookie('kyte_iden', '', -1);
					// reset to defaults
					obj.access_key = obj.initial_access_key;
					obj.identifier = obj.initial_identifier;
					obj.account_number = obj.initial_account_number;
				}
				if (!response.token && !response.session) {
					obj.setCookie('txToken', '', -1);
					obj.setCookie('sessionToken', '', -1);
					obj.setCookie('sessionPermission', '', -1);
					// destroy api handoff cookies
					obj.setCookie('kyte_pub', '', -1);
					obj.setCookie('kyte_num', '', -1);
					obj.setCookie('kyte_iden', '', -1);
					// reset to defaults
					obj.access_key = obj.initial_access_key;
					obj.identifier = obj.initial_identifier;
					obj.account_number = obj.initial_account_number;
				} else {
					obj.setCookie('txToken', obj.txToken, 60);
					obj.setCookie('sessionToken', obj.sessionToken, 60);
					obj.setCookie('sessionPermission', response.sessionPermission, 60);
				}

		        if (typeof callback === "function") {
		      		callback(response);
		      	} else {
		      		console.log(response);
		      	}
			},
			error: function(response) {
				if (response.status == 403) {
					obj.setCookie('txToken', '', -1);
					obj.setCookie('sessionToken', '', -1);
					obj.setCookie('sessionPermission', '', -1);
				} else {
					obj.txToken = response.responseJSON.token;
					obj.sessionToken = response.responseJSON.session;
					if (response.kyte_pub && response.kyte_iden && response.kyte_num) {
						obj.access_key = response.kyte_pub;
						obj.identifier = response.kyte_iden;
						obj.account_number = response.kyte_num;
						obj.setCookie('kyte_pub', obj.access_key, 60);
						obj.setCookie('kyte_iden', obj.identifier, 60);
						obj.setCookie('kyte_num', obj.account_number, 60);
					} else {
						// destroy api handoff cookies
						obj.setCookie('kyte_pub', '', -1);
						obj.setCookie('kyte_num', '', -1);
						obj.setCookie('kyte_iden', '', -1);
						// reset to defaults
						obj.access_key = obj.initial_access_key;
						obj.identifier = obj.initial_identifier;
						obj.account_number = obj.initial_account_number;
					}
					if (!response.responseJSON.token && !response.responseJSON.session) {
						obj.setCookie('txToken', '', -1);
						obj.setCookie('sessionToken', '', -1);
						obj.setCookie('sessionPermission', '', -1);
						// destroy api handoff cookies
						obj.setCookie('kyte_pub', '', -1);
						obj.setCookie('kyte_num', '', -1);
						obj.setCookie('kyte_iden', '', -1);
						// reset to defaults
						obj.access_key = obj.initial_access_key;
						obj.identifier = obj.initial_identifier;
						obj.account_number = obj.initial_account_number;
					} else {
						obj.setCookie('txToken', obj.txToken, 60);
						obj.setCookie('sessionToken', obj.sessionToken, 60);
						obj.setCookie('sessionPermission', response.responseJSON.sessionPermission, 60);
					}
				}

		      	if (typeof error === "function") {
		      		error(response.responseJSON.error);
		      	} else {
		      		console.log(response.responseJSON.error);
			        alert(response.responseJSON.error);
			    }
			}
	    });
	},
	function(response) {
		if (typeof error === "function") {
			error(response);
		} else {
			console.log(response);
			alert(response);
		}
	});
};

/* Post
 *
 * Use sign() to obtain authorization to transact and
 * send data accompanied with signature data
 *
 */
Kyte.prototype.post = function(model, data = null, formdata = null, callback, error = null) {
	this.sendData('POST', model, null, null, data, formdata, callback, error);
};

/* Put
 *
 * Use sign() to obtain authorization to transact and
 * send data accompanied with signature data
 *
 */
Kyte.prototype.put = function(model, field = null, value = null, data = null, formdata = null, callback, error = null) {
	this.sendData('PUT', model, field, value, data, formdata, callback, error);
};

/* Get
 *
 * Use sign() to obtain authorization to transact and
 * send data accompanied with signature data
 *
 */
Kyte.prototype.get = function(model, field = null, value = null, callback, error = null) {
	this.sendData('GET', model, field, value, null, null, callback, error);
};

/* Delete
 *
 * Use sign() to obtain authorization to transact and
 * send data accompanied with signature data
 *
 */
Kyte.prototype.delete = function(model, field = null, value = null, callback, error = null) {
	this.sendData('DELETE', model, field, value, null, null, callback, error);
};

/* 
 * Set browser cookie
 */
Kyte.prototype.setCookie = function (cname, cvalue, minutes) {
	var d = new Date();
	d.setTime(d.getTime() + (minutes*60*1000));
	var expires = "expires="+ d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;" + (location.protocol === 'https:' ? 'secure;' : '');
};

/* 
 * Get browser cookie
 */
Kyte.prototype.getCookie = function (cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
};

/* 
 * Get params from URL
 */
Kyte.prototype.getUrlParameter = function(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};
Kyte.prototype.getPageRequest = function() {
	let encoded = this.getUrlParameter('request');
	let decoded = decodeURIComponent(atob(encoded));
	let obj = JSON.parse(decoded);

	return obj;
}

Kyte.prototype.initSpinner = function(selector) {
	selector.append('<div id="pageLoaderModal" class="modal" style="background: white; opacity: 0.6;" data-backdrop="static" data-keyboard="false" tabindex="-1"><div class="modal-dialog modal-sm h-100 d-flex"><div class="mx-auto align-self-center" style="width: 48px"><div class="spinner-wrapper text-center fa-6x"><span class="fas fa-sync fa-spin"></span></div></div></div></div>');
};
Kyte.prototype.startSpinner = function() {
	$('#pageLoaderModal').modal();
}
Kyte.prototype.stopSpinner = function() {
	$('#pageLoaderModal').modal('hide');
}

/* 
 * Request backend to create new session
 *
 * like all api requests, first obtain a transaction
 * authorization from sign() then pass email and password.
 * If user is valid then create cookie with token; otherwise
 * redirect users to login page.
 * 
 */
Kyte.prototype.sessionCreate = function(identity, callback, error = null, sessionController = 'Session') {
	var obj = this;
	this.post(sessionController, identity, null,
	function(response) {
		obj.txToken = response.data.txToken;
		obj.sessionToken = response.data.sessionToken;
		obj.setCookie('txToken', obj.txToken, 60);
		obj.setCookie('sessionToken', obj.sessionToken, 60);
		obj.setCookie('sessionPermission', response.data.User.role, 60);
		// set api handoff cookies
		obj.access_key = response.kyte_pub;
		obj.identifier = response.kyte_iden;
		obj.account_number = response.kyte_num;
		obj.setCookie('kyte_pub', obj.access_key, 60);
		obj.setCookie('kyte_iden', obj.identifier, 60);
		obj.setCookie('kyte_num', obj.account_number, 60);
		if (typeof callback === "function") {
			callback(response);
		} else {
			console.log(response);
		}
	},
	function(response) {
		// destroy session cookies
		obj.setCookie('txToken', '', -1);
		obj.setCookie('sessionToken', '', -1);
		obj.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		obj.setCookie('kyte_pub', '', -1);
		obj.setCookie('kyte_num', '', -1);
		obj.setCookie('kyte_iden', '', -1);
		// reset to defaults
		obj.access_key = obj.initial_access_key;
		obj.identifier = obj.initial_identifier;
		obj.account_number = obj.initial_account_number;
		if (typeof error === "function") {
			error(response);
		} else {
			console.log(response);
			alert(response);
		}
	});
};

Kyte.prototype.addLogoutHandler = function(selector) {
	self = this;
	selector.click(function () {
		self.sessionDestroy(function () {
			location.href = "/";
		});
	});
}

Kyte.prototype.isSession = function() {
	if (this.sessionToken == 0 || this.sessionToken == '0') {
		this.setCookie('sessionToken', '', -1);
		this.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		this.setCookie('kyte_pub', '', -1);
		this.setCookie('kyte_num', '', -1);
		this.setCookie('kyte_iden', '', -1);
		// reset to defaults
		this.access_key = this.initial_access_key;
		this.identifier = this.initial_identifier;
		this.account_number = this.initial_account_number;
	}
	if (this.txToken == 0 || this.txToken == '0') {
		this.setCookie('txToken', '', -1);
		this.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		this.setCookie('kyte_pub', '', -1);
		this.setCookie('kyte_num', '', -1);
		this.setCookie('kyte_iden', '', -1);
		// reset to defaults
		this.access_key = this.initial_access_key;
		this.identifier = this.initial_identifier;
		this.account_number = this.initial_account_number;
	}
	if (!this.sessionToken || !this.txToken) {
		this.setCookie('txToken', '', -1);
		this.setCookie('sessionToken', '', -1);
		this.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		this.setCookie('kyte_pub', '', -1);
		this.setCookie('kyte_num', '', -1);
		this.setCookie('kyte_iden', '', -1);
		// reset to defaults
		this.access_key = this.initial_access_key;
		this.identifier = this.initial_identifier;
		this.account_number = this.initial_account_number;
	}
	return (this.getCookie('sessionToken') ? true : false);
}

Kyte.prototype.isAdmin = function() {
	return this.getCookie("sessionPermission") == this.adminRole;
}

/* 
 * Request backend to destroy session
 *
 * like all api requests, first obtain a transaction
 * authorization from sign() then pass session token from cookie.
 * If session token is valid then log user out.
 * 
 */
Kyte.prototype.sessionDestroy = function(error = null) {
	var obj = this;
	this.delete('Session', null, null,
	function(response) {
		obj.setCookie('txToken', '', -1);
		obj.setCookie('sessionToken', '', -1);
		obj.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		obj.setCookie('kyte_pub', '', -1);
		obj.setCookie('kyte_num', '', -1);
		obj.setCookie('kyte_iden', '', -1);
		// reset to defaults
		obj.access_key = obj.initial_access_key;
		obj.identifier = obj.initial_identifier;
		obj.account_number = obj.initial_account_number;
		if (typeof error === "function") {
			error(response);
		} else {
			console.log(response);
			alert(response);
		}
	},
	function(response) {
		obj.setCookie('txToken', '', -1);
		obj.setCookie('sessionToken', '', -1);
		obj.setCookie('sessionPermission', '', -1);
		// destroy api handoff cookies
		obj.setCookie('kyte_pub', '', -1);
		obj.setCookie('kyte_num', '', -1);
		obj.setCookie('kyte_iden', '', -1);
		// reset to defaults
		obj.access_key = obj.initial_access_key;
		obj.identifier = obj.initial_identifier;
		obj.account_number = obj.initial_account_number;
		if (typeof error === "function") {
			error(response);
		} else {
			console.log(response);
			alert(response);
		}
	});
}

Kyte.prototype.makeid = function(length) {
	var result           = '';
	var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	var charactersLength = characters.length;
	for ( var i = 0; i < length; i++ ) {
	   result += characters.charAt(Math.floor(Math.random() * charactersLength));
	}
	return result;
 };

Kyte.prototype.alert = function(title, message, callback = null) {
	let id = this.makeid(5);
	$('<div></div>').appendTo('body').html('<div><h6>'+message+'</h6></div>')
	.dialog({
		modal: true,
		title: title,
		zIndex: 10000,
		autoOpen: true,
		width: 'auto',
		resizeable: false,
		buttons: {
			OK: function() {
				if (typeof callback === "function") {
					callback();
				}
				$(this).dialog('close');
			}
		},
		close: function(e, ui) {
			$(this).remove();
		}
	});
};

Kyte.prototype.confirm = function(title, message, callback = null, cancel = null) {
	let id = this.makeid(5);
	$('<div></div>').appendTo('body').html('<div><h6>'+message+'</h6></div>')
	.dialog({
		modal: true,
		title: title,
		zIndex: 10000,
		autoOpen: true,
		width: 'auto',
		resizeable: false,
		buttons: {
			Yes: function() {
				if (typeof callback === "function") {
					callback();
				}
				$(this).dialog('close');
			},
			No: function() {
				if (typeof cancel === "function") {
					cancel();
				}
				$(this).dialog('close');
			}
		},
		close: function(e, ui) {
			$(this).remove();
		}
	});
};

/* 
 * Check password minimums and update UI
 */
Kyte.prototype.validatePassword = function(obj) {
	var pswd = obj.val();

	// check password length
    if ( pswd.length < 6 ) {
		obj.removeClass('is-valid').addClass('is-invalid');
		$('ul li.validate-length i').removeClass('fa-circle');
		$('ul li.validate-length i').removeClass('fa-check-circle').addClass('fa-times-circle');
		$('ul li.validate-length i').removeClass('text-success').addClass('text-danger');
		return false;
	} else {
		obj.removeClass('is-invalid').addClass('is-valid');
		$('ul li.validate-length i').removeClass('fa-circle');
		$('ul li.validate-length i').removeClass('fa-times-circle').addClass('fa-check-circle');
		$('ul li.validate-length i').removeClass('text-danger').addClass('text-success');
	}

	//validate letter
	if ( pswd.match(/[A-z]/) ) {
		obj.removeClass('is-invalid').addClass('is-valid');
		$('ul li.validate-small i').removeClass('fa-circle');
		$('ul li.validate-small i').removeClass('fa-times-circle').addClass('fa-check-circle');
		$('ul li.validate-small i').removeClass('text-danger').addClass('text-success');
	} else {
		obj.removeClass('is-valid').addClass('is-invalid');
		$('ul li.validate-small i').removeClass('fa-circle');
		$('ul li.validate-small i').removeClass('fa-check-circle').addClass('fa-times-circle');
		$('ul li.validate-small i').removeClass('text-success').addClass('text-danger');
		return false;
	}

	//validate capital letter
	if ( pswd.match(/[A-Z]/) ) {
		obj.removeClass('is-invalid').addClass('is-valid');
		$('ul li.validate-large i').removeClass('fa-circle');
		$('ul li.validate-large i').removeClass('fa-times-circle').addClass('fa-check-circle');
		$('ul li.validate-large i').removeClass('text-danger').addClass('text-success');
	} else {
		obj.removeClass('is-valid').addClass('is-invalid');
		$('ul li.validate-large i').removeClass('fa-circle');
		$('ul li.validate-large i').removeClass('fa-check-circle').addClass('fa-times-circle');
		$('ul li.validate-large i').removeClass('text-success').addClass('text-danger');
		return false;
	}

	//validate number
	if ( pswd.match(/\d/) ) {
		obj.removeClass('is-invalid').addClass('is-valid');
		$('ul li.validate-number i').removeClass('fa-circle');
		$('ul li.validate-number i').removeClass('fa-times-circle').addClass('fa-check-circle');
		$('ul li.validate-number i').removeClass('text-danger').addClass('text-success');
	} else {
		obj.removeClass('is-valid').addClass('is-invalid');
		$('ul li.validate-number i').removeClass('fa-circle');
		$('ul li.validate-number i').removeClass('fa-check-circle').addClass('fa-times-circle');
		$('ul li.validate-number i').removeClass('text-success').addClass('text-danger');
		return false;
	}

	// symbol
	if (pswd.match(/[@$!%*#?&]/)) {
		obj.removeClass('is-invalid').addClass('is-valid');
		$('ul li.validate-symbol i').removeClass('fa-circle');
		$('ul li.validate-symbol i').removeClass('fa-times-circle').addClass('fa-check-circle');
		$('ul li.validate-symbol i').removeClass('text-danger').addClass('text-success');
	} else {
		obj.removeClass('is-valid').addClass('is-invalid');
		$('ul li.validate-symbol i').removeClass('fa-circle');
		$('ul li.validate-symbol i').removeClass('fa-check-circle').addClass('fa-times-circle');
		$('ul li.validate-symbol i').removeClass('text-success').addClass('text-danger');
		return false;
	}

	return true;
}

Kyte.prototype.validateForm = function(form) {
	let valid = true;
	form.find('input').each(function(){
		if ($(this).prop('required') && !$(this).val()) {
			valid = false;
		}
	});
	return valid;
}

/*
 * Class Definition for Kyte Table
 *
 * api : Kyte object
 * selector : id tag
 * model : json array defining model { 'name' : <model name>, 'field' : <null/field name>, 'value' : <null/field value> }
 * def : json array with table definition 
 * 		Definition:
 * 		- targets (required)
 * 		- data (required)
 * 		- label (required)
 * 		- visible (optional) true/false
 * 		- sortable (optional) true/false
 * 		- render (optional) function (data, type, row, meta) {}
 * order : array of order [[0, 'asc'], [1,'asc']]
 * rowCallBack : optional function(row, data, index) {}
 * initComplete : optional function() {}
 */
function KyteTable(api, selector, model, columnDefs, searching = true, order = [], actionEdit = false, actionDelete = false, actionView = false, viewTarget = null, rowCallBack = null, initComplete = null, lang = "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/English.json") {
	this.api = api;
	this.model = model;

	this.loaded = false;
	this.table = null;

	this.selector = selector;
	this.lang = lang;
	this.searching = searching;

	this.columnDefs = columnDefs;
	this.order = order;

	this.actionEdit = actionEdit;
	this.editForm = null;
	this.actionDelete = actionDelete;
	this.actionView = actionView;
	this.viewTarget = viewTarget;
	this.rowCallBack = rowCallBack;
	this.initComplete = initComplete;

	this.pageLength = 50;
};

KyteTable.prototype.init = function() {
	let self = this;
	if (!this.loaded) {
		this.api.get(this.model.name, this.model.field, this.model.value, function (response) {
			let content = '<thead><tr>';
			let i = 0;
			self.columnDefs.forEach(function (item) {
				content += '<th class="'+item.data.replace(/\./g, '_')+'">'+item.label+'</th>';
				delete self.columnDefs[i]['label'];
				i++;
			});
			if (self.actionEdit || self.actionDelete) {
				// add column for actions
				content += '<th></th>';
				// calculate new target
				let targetIdx = self.columnDefs.length;
				var actionHTML = '';
				if (self.actionEdit) {
					actionHTML += '<a class="mr-3 edit btn btn-small btn-outline-primary" href="#"><i class="fas fa-edit"></i></a>';
				}
				if (self.actionDelete) {
					actionHTML += '<a class="mr-3 delete btn btn-small btn-outline-danger" href="#"><i class="fas fa-trash-alt"></i></a>';
					// bind listener
					self.selector.on('click', '.delete', function (e) {
						e.preventDefault();
						let row = self.table.row($(this).parents('tr'));
						let data = row.data();
						self.api.confirm('Delete', 'Are you sure you wish to delete?', function() {
							self.api.delete(self.model.name, 'id', data['id'], function() {
								row.remove().draw();
							}, function() {
								alert('Unable to delete. Please try again later.');
							});
						});
					});
				}
				self.columnDefs.push({
					"targets": targetIdx,
					"sortable": false,
					"data": "",
					"className": "text-right row-actions",
					render: function (data, type, row, meta) {
						const returnString = actionHTML;
						return returnString;
					}
				});
			}
			if (self.actionView) {
				self.selector.on('click', 'tbody tr', function (e) {
					e.preventDefault();
					let row = self.table.row(this);
					let data = row.data();
					if (self.viewTarget) {
						let obj = {'model': self.model.name,'idx':data[self.actionView]};
						let encoded = encodeURIComponent(btoa(JSON.stringify(obj)));
						location.href=self.viewTarget+"?request="+encoded;
					}
				});
				self.selector.on('click', 'tbody td.row-actions', function (e) {
					e.stopPropagation();
				});
			}
			content += '</tr></thead><tbody></tbody>';
			self.selector.append(content);
			self.table = self.selector.DataTable({
				searching: self.searching,
				responsive: true,
				language: { "url": self.lang },
				data: response.data,
				columnDefs: self.columnDefs,
				order: self.order,
				pageLength: self.pageLength,
				rowCallback: self.rowCallBack,
				initComplete: self.initComplete
			});
			self.loaded = true;
			// initialize hand pointer if frow is clickable
			if (self.actionView) {
				self.selector.find('tbody').addClass('row-pointer-hand');
				$('<style>tbody.row-pointer-hand tr { cursor: pointer } tbody td {vertical-align: middle !important;}</style>').appendTo('body');
			}
		}, function() {
			alert("Unable to load data");
		});
	}
};

KyteTable.prototype.bindEdit = function(editForm) {
	var self = this;
	self.editForm = editForm;
	if (this.actionEdit) {
		// bind listener
		this.selector.on('click', '.edit', function (e) {
			e.preventDefault();
			self.editForm.selectedRow = self.table.row($(this).parents('tr'));
			let data = self.editForm.selectedRow.data();
			self.editForm.setID(data['id']);
			self.editForm.showModal();
		});
	}
}

/*
 * Class Definition for Kyte Form
 *
 * api : Kyte object
 * selector : id tag
 * hidden : json array of hidden elements (do not include id as it will clash)
 * 
 * {
 * 	'name' : '<field_name>',
 * 	'value' : '<null/value>'
 * }
 * 
 * elements : json array defining form elements
 * 
 * [
 * 	[x-> direction],
 * 	[x-> direction],
 * 	[x-> direction], ...etc...
 * ]
 * 
 * {
 * 	'field' : '<model_attribute>',
 * 	'type' : '<text/password/select/textarea>',
 * 	'label' : '<label>',
 * 	'placeholder' : '<placeholder>',
 * 	'required' : true/false,
 * 
 * For dates:
 * 'date': true/false,
 * 
 * 	### if field type is select, the following is required to set options
 * 	## For using ajax data source:
 * 	'option' : {
 *	 	'ajax' : true,
 * 		'data_model_name' : '<model_name>',
 * 		'data_model_field' : <null/field_name>,
 * 		'data_model_value' : <null/value>,
 * 		'data_model_attribute' : <attribute_name>
 * 	}
 * 
 * 	## For using predefined values:
 * 	'option' : {
 *	 	'ajax' : false,
 *		'data' : {
 *	 		'<option_value_1>' : '<option_name_1>',
 * 			'<option_value_2>' : '<option_name_2>',
 * 			'<option_value_3>' : '<option_name_3>',...etc....
 *		}
 * 	}
 * }
 * 
 * successCallBack : optional function() {}
 * failureCallBack : option function() {}
 */
function KyteForm(api, selector, modelName, hiddenFields, elements, title = 'Form', table = null, modal = false, modalButton = null,  successCallBack = null, failureCallBack = null) {
	this.api = api;
	this.model = modelName;
	this.modal = modal;
	this.modalButton = modalButton;
	this.KyteTable = table;
	this.title = title;
	this.hiddenFields = hiddenFields;
	this.elements = elements;
	this.id;
	this.submitButton = 'Submit';

	this.itemized = false;

	this.success = successCallBack;
	this.fail = failureCallBack;

	this.loaded = false;

	this.selector = selector;

	this.selectedRow = null;
}

KyteForm.prototype.init = function() {
	if (!this.loaded) {
		this.id = this.makeID(8);
		let content = '';
		let obj = this;

		// if modal, then create modal tags
		if (this.modal) {
			// add click listener to modal button
			if (this.modalButton) {
				this.modalButton.on('click', function() {
					$('#modal_'+obj.model+'_'+obj.id).modal('show');
				});
			}

			content += '\
<div class="modal fade" id="modal_'+this.model+'_'+this.id+'" tabindex="-1" role="dialog" aria-labelledby="modal_'+this.model+'_'+this.id+'" aria-hidden="true">\
	<div class="modal-dialog modal-lg" role="document">\
		<div class="modal-content">\
			<div class="modal-header text-center">\
				<h4 class="modal-title w-100 font-weight-bold">'+this.title+'</h4>\
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\
			</div>\
			<div class="modal-body mx-3">';
		}

		// form content
		content += '\
				<div id="'+this.model+'_'+this.id+'_modal-loader" class="modal" style="background: white; opacity: 0.6;" data-backdrop="static" data-keyboard="false" tabindex="-1">\
					<div class="modal-dialog modal-sm h-100 d-flex">\
						<div class="mx-auto align-self-center" style="width: 48px">\
							<div class="spinner-wrapper text-center fa-6x"><span class="fas fa-sync fa-spin"></span></div>\
						</div>\
					</div>\
				</div>\
				<form novalidate="novalidate" class="needs-validation" id="form_'+this.model+'_'+this.id+'">\
					<div class="error-msg text-danger"></div>';

		// append hidden fields
		if (this.hiddenFields) {
			this.hiddenFields.forEach(function(field) {
				content += '\
					<input type="hidden" id="form_'+obj.model+'_'+obj.id+'_'+field.name+'" name="'+field.name+'" value="'+field.value+'">';
			});
		}
		
				// iterate through form definition
		this.elements.forEach(function (row) {
			content += '\
					<div class="row">';
			row.forEach(function (column) {
				content += '\
						<div class="col-sm">\
							<div class="form-group">';

				// if label option is provided
				if (column.label) {
					content += '\
								<label for="form_'+obj.model+'_'+obj.id+'_'+column.field+'">'+column.label+'</label>';
				}

				if (column.type == 'select') {
					content += '\
								<select class="custom-select" id="form_'+obj.model+'_'+obj.id+'_'+column.field+'" class="form-control" name="'+column.field+'"';
					content += column.required ? 'required="required"' : '';
					content += '>';
					// if not ajax, then populate with data - ajax will populate after appending html
					if (!column.option.ajax) {
						for (var key in column.option.data) {
							if (column.option.data.hasOwnProperty(key)) {
								content += '\
									<option value="'+key+'">'+column.option.data[key]+'</option>';
							}
						}
					}
					// close select
					content += '\
								</select>';
				} else if (column.type == 'textarea') {
					content += '\
								<textarea style="width:100%" id="form_'+obj.model+'_'+obj.id+'_'+column.field+'" name="'+column.field+'"';
					content += column.required ? 'required="required"' : '';
					if (column.placeholder) {
						content += ' placeholder="'+column.placeholder+'"';
					}
					content += '></textarea>';
				} else {
					content += '\
								<input type="'+column.type+'" id="form_'+obj.model+'_'+obj.id+'_'+column.field+'" class="form-control'+(column.date ? ' form-datepicker':'')+'" name="'+column.field+'"';
					content += column.required ? 'required="required"' : '';
					if (column.placeholder) {
						content += ' placeholder="'+column.placeholder+'"';
					}
					content += '>';
				}

				content += '\
							</div>\
						</div>';
			});
			content += '\
					</div>';
		});

		// if itemized is specified, populate with template information
		if (this.itemized) {
			content += '\
					<hr>\
					<h6>'+this.itemized.title+'</h6><div class="row">';

			// add column headers
			this.itemized.fields.forEach(function(field) {
				content += '<div class="col">'+field.label+'</div>';
			});

			content += '<div class="col"></div></div><div id="itemized_'+this.model+'_'+this.id+'"></div>\
					<div class="row my-4"><div class="col text-right"><a href="#" class="itemized-add-item btn btn-small btn-outline-secondary">Add</a></div></div>\
					<hr>';
		}

		content += '<div class="row my-4"><div class="col text-center"><input type="submit" name="submit" value="'+obj.submitButton+'" class="btn btn-primary btn-medium d-none d-sm-inline-block"></div></div>';

		// end form
		content += '\
				</form>';

		// if modal, then close modal tags
		if (this.modal) {
			content += '\
			</div>\
		</div>\
	</div>\
</div>';
		}
		this.selector.append(content);

		this.reloadAjax();

		// initialize datepicker
		this.elements.forEach(function (row) {
			row.forEach(function (column) {
				if (column.date) {
					$('#form_'+obj.model+'_'+obj.id+'_'+column.field).datepicker();
					$('#form_'+obj.model+'_'+obj.id+'_'+column.field).datepicker("option", "dateFormat", obj.api.dateFormat);
				}
			});
		});

		// bind submit listener
		$('#form_'+this.model+'_'+this.id).submit(function(e) {
			var form = $(this);
			e.preventDefault();

			// validate and make sure required fields are filled
			var valid = true;
			form.find('input').each(function () { if ($(this).prop('required') && !$(this).val()) { valid = false; $(this).addClass('is-invalid'); $(this).removeClass('is-valid'); } else { $(this).addClass('is-valid'); $(this).removeClass('is-invalid'); } });
			form.find('textarea').each(function () { if ($(this).prop('required') && !$(this).val()) { valid = false; $(this).addClass('is-invalid'); $(this).removeClass('is-valid'); } else { $(this).addClass('is-valid'); $(this).removeClass('is-invalid'); } });
			
			// if valid, prep to send data
			if (valid) {
				// open model
				$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('show');
				// if an ID is set, then update entry
				let idx = form.data('idx');
				if (idx > 0) {
					obj.api.put(obj.model, 'id', idx, null, form.serialize(),
						function (response) {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');

							// run call back function if any
							if (typeof obj.success === "function") {
								obj.success(response)
							}

							if (obj.KyteTable) {
								obj.selectedRow.data(response.data).draw();
							}

							// close modal if form is a modal dialog
							obj.hideModal();
						},
						function (response) {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');
							$('#form_'+obj.model+'_'+obj.id+' .error-msg').html(response);
							if (typeof obj.success === "function") {
								obj.fail(response)
							}
						}
					);
				}
				// else, create new entry
				else {
					obj.api.post(obj.model, null, form.serialize(),
						function (response) {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');

							if (obj.KyteTable) {
								if (response.data.constructor === Array) {
									response.data.forEach(function(item) {
										// update data table
									obj.KyteTable.table.row.add(item).draw();
									});
								} else {
									// update data table
									obj.KyteTable.table.row.add(response.data).draw();
								}
							}

							// run call back function if any
							if (typeof obj.success === "function") {
								obj.success(response)
							}

							// close modal if form is a modal dialog
							obj.hideModal();
						},
						function (response) {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');
							$('#form_'+obj.model+'_'+obj.id+' .error-msg').html(response);
							if (typeof obj.success === "function") {
								obj.fail(response)
							}
						}
					);
				}
			}
		});

		// initializer for modal
		if (this.modal) {
			// bind modal hide listener - reset form
			$('#modal_'+this.model+'_'+this.id).on('hidden.bs.modal', function (e) {
				if (e.target.id == 'modal_'+obj.model+'_'+obj.id) {
					var form = $('#form_'+obj.model+'_'+obj.id);
					form.data('idx','');
					form.data('row','');
					form.find('.error-msg').html('');
					form.find('input').each(function () {
						$(this).removeClass('is-invalid');
						$(this).removeClass('is-valid');
					});
					form.find('input[type="text"').each(function () {
						$(this).val('');
					});
					form.find('input[type="email"').each(function () {
						$(this).val('');
					});
					form.find('input[type="password"').each(function () {
						$(this).val('');
					});
					form.find('input[type="tel"').each(function () {
						$(this).val('');
					});
					form.find('input[type="checkbox"').each(function () {
						$(this).prop('checked', false);
					});
					form.find('input[type="radio"').each(function () {
						$(this).prop('checked', false);
					});
					form.find('select').each(function () {
						$(this).prop('selectedIndex', 0);
					});
					form.find('textarea').each(function () {
						$(this).val('');
					});
					if (obj.itemized) {
						$('#itemized_'+obj.model+'_'+obj.id).html('');
					}
				}
			});

			$('#modal_'+this.model+'_'+this.id).on('shown.bs.modal', function (e) {
				if (e.target.id == 'modal_'+obj.model+'_'+obj.id) {
					var form = $('#form_'+obj.model+'_'+obj.id);
					let idx = form.data('idx');
					// check if idx is set and retrieve information
					if (idx) {
						$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('show');

						obj.loadFormData(idx, function() {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');
						}, function() {
							$('#'+obj.model+'_'+obj.id+'_modal-loader').modal('hide');
							obj.hideModal();
							alert('Unable to load form. Please try again later');
						});
					}
				}
			});
		}

		if (this.itemized) {
			// bind listener for itemized
			$('#form_'+this.model+'_'+this.id).on('click', '.itemized-add-item', function(e) {
				var uniqueId = obj.makeID(8);	// ID used to track newly created selects to populate if Ajax is set to true
				
				e.preventDefault(); // prevent default link behaviour
				
				let itemizedHTML = '<div class="row itemized-row">';	// init html string

				obj.itemized.fields.forEach(function(field) {
					itemizedHTML += '<div class="col"><div class="form-group">'
					if (field.type == 'select') {
						itemizedHTML += '<select id="'+field.option.data_model_name+'_'+field.option.data_model_value+'_'+uniqueId+'" class="custom-select" id="itemized_'+obj.model+'_'+obj.id+'_'+field.name+'" class="form-control" name="'+field.name+'"';
						itemizedHTML += field.required ? 'required="required"' : '';
						itemizedHTML += '>';
						// if not ajax, then populate with data - ajax will populate after appending html
						if (!field.option.ajax) {
							for (var key in field.option.data) {
								if (field.option.data.hasOwnProperty(key)) {
									itemizedHTML += '<option value="'+key+'">'+field.option.data[key]+'</option>';
								}
							}
						}
						// close select
						itemizedHTML += '</select>';
					} else if (field.type == 'textarea') {
						itemizedHTML += '<textarea style="width:100%" id="itemized_'+obj.model+'_'+obj.id+'_'+field.name+'" name="'+field.name+'"';
						itemizedHTML += field.required ? 'required="required"' : '';
						if (field.placeholder) {
							itemizedHTML += ' placeholder="'+field.placeholder+'"';
						}
						itemizedHTML += '></textarea>';
					} else {
						itemizedHTML += '<input type="'+field.type+'" id="itemized_'+obj.model+'_'+obj.id+'_'+field.name+'" class="form-control'+(field.date ? ' form-datepicker':'')+'" name="'+field.name+'"';
						itemizedHTML += field.required ? 'required="required"' : '';
						if (field.placeholder) {
							itemizedHTML += ' placeholder="'+field.placeholder+'"';
						}
						itemizedHTML += '>';
					}
					itemizedHTML += '</div></div>';
				});
				itemizedHTML += '<div class="col-2 text-right"><a href="#" class="itemized-delete-item btn btn-small btn-outline-danger">remove</a></div></div>';
				// append fields
				$('#itemized_'+obj.model+'_'+obj.id).append(itemizedHTML);

				// run ajax for any selects
				obj.itemized.fields.forEach(function(field) {
					if (field.type == 'select') {
						if (field.option.ajax) {
							obj.api.get(field.option.data_model_name, field.option.data_model_field, field.option.data_model_value, function(response) {
								response.data.forEach(function(item) {
									let label = '';
									field.option.data_model_attributes.forEach(function(attribute) {
										if (item[attribute]) {
											label += item[attribute]+' ';
										} else {
											// attempt to split by dot notation
											let c = attribute.split('.');
											if (c.length >= 2) {
												label += item[c[0]][c[1]]+' ';
											} else {
												label += attribute+' ';
											}
										}
									});
									$('#'+field.option.data_model_name+'_'+field.option.data_model_value+'_'+uniqueId).append('<option value="'+item[field.option.data_model_value]+'">'+label+'</option>');
								});
							});
						}
					}
				});
			});

			$('#form_'+this.model+'_'+this.id).on('click', '.itemized-delete-item', function(e) {
				e.preventDefault();
				$(this).closest('.itemized-row').remove();
			});
		}

		this.loaded = true;
	}
};
KyteForm.prototype.showModal = function() {
	if (this.modal) {
		$('#modal_'+this.model+'_'+this.id).modal('show');
	}
}
KyteForm.prototype.hideModal = function() {
	if (this.modal) {
		$('#modal_'+this.model+'_'+this.id).modal('hide');
	}
}
KyteForm.prototype.loadFormData = function(idx, success = null, fail = null) {
	var obj = this;
	obj.api.get(obj.model, 'id', idx, function(response) {
		// populate form

		// do not populate hidden fields as return data is object....
		// if (obj.hiddenFields) {
		// 	obj.hiddenFields.forEach(function(field) {
		// 		$('#form_'+obj.model+'_'+obj.id+'_'+field.name).val(response.data[0][field.name]);
		// 	});
		// }

		// next form visible elements
		obj.elements.forEach(function (row) {
			row.forEach(function (column) {
				if (typeof response.data[0][column.field] === 'object' && response.data[0][column.field] !== null) {
					$('#form_'+obj.model+'_'+obj.id+'_'+column.field).val(response.data[0][column.field].id).change();
				} else {
					$('#form_'+obj.model+'_'+obj.id+'_'+column.field).val(response.data[0][column.field]).change();
				}
			});
		});

		if (typeof success === "function") {
			success();
		}
	}, function() {
		if (typeof success === "function") {
			success();
		}
	});
}
KyteForm.prototype.reloadAjax = function() {
	let obj = this;
	// if ajax, then populate data
	this.elements.forEach(function (row) {
		row.forEach(function (column) {
			if (column.type == 'select') {
				if (column.option.ajax) {
					$("#form_"+obj.model+"_"+obj.id+'_'+column.field).html('');
					obj.api.get(column.option.data_model_name, column.option.data_model_field, column.option.data_model_value, function (response) {
						response.data.forEach(function(item) {
							let label = '';
							column.option.data_model_attributes.forEach(function(attribute) {
								if (item[attribute]) {
									label += item[attribute]+' ';
								} else {
									// attempt to split by dot notation
									let c = attribute.split('.');
									if (c.length >= 2) {
										label += item[c[0]][c[1]]+' ';
									} else {
										label += attribute+' ';
									}
								}
							});
							$("#form_"+obj.model+"_"+obj.id+'_'+column.field).append('<option value="'+item['id']+'">'+label+'</option>');
						});
					}, function() {
						alert("Unable to load data");
					});
				}
			}
		});
	});
}
KyteForm.prototype.setID = function(idx) {
	$('#form_'+this.model+'_'+this.id).data('idx',idx);
}
KyteForm.prototype.getID = function() {
	return $('#form_'+this.model+'_'+this.id).data('idx');
}
KyteForm.prototype.makeID = function(length) {
	var result           = '';
	var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	var charactersLength = characters.length;
	for ( var i = 0; i < length; i++ ) {
		result += characters.charAt(Math.floor(Math.random() * charactersLength));
	}
	return result;
};
