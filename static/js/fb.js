var OW_FBConstructor = function(libUrl, loginOptions, options)
{
	var self = this;

	var redirect = function(href){
		if (href) {
			window.location.href = href;
		} else {
			window.location.reload(true);
		}
	};

	this.delegates = {
                beforeInit: function(){

                },

                afterInit: function(FB){

                },

		onLogin: function(r){
			redirect(options.onLoginUrl);
		},

		onSynchronize: function(){
			redirect(options.onSynchronizeUrl);
		}
	};


        this.init = function(params)
        {
            this.delegates.beforeInit();
            $('body').prepend('<div id="fb-root"></div>');

            window.fbAsyncInit = function() {
                FB.init(params);

                self.delegates.afterInit(FB);
            };

            (function() {
                var e = document.createElement('script');
                e.src = libUrl;
                e.async = true;
                document.getElementById('fb-root').appendChild(e);
            }());
        }

        this.requireLogin = function(func)
        {
            FB.getLoginStatus(function(response)
            {
                if (response.authResponse)
                {
                    func(response);
                }
                else
                {
                    FB.login(function(r)
                    {
                        if (r.authResponse)
                        {
                            func(r);
                        }

                    }, loginOptions);
                }
            });
        };

	this.login = function()
        {
            this.requireLogin(function(r){
                self.delegates.onLogin(r);
            });
	};

	this.synchronize = function(delegate)
        {
            this.requireLogin(function(r){
                self.delegates.onSynchronize(r);
            });
	};
};