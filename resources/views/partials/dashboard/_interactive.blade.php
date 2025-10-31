<div class="row">
    <div class="col-md-12">
        <div class="panel" data-ave-tabs-root>
            <div class="panel-heading">
                <ul class="nav-tabs" data-ave-tabs="nav">
                    <li class="active" data-ave-tab-target="#tab-home">
                        <a href="#tab-home">Home</a>
                    </li>
                    <li data-ave-tab-target="#tab-profile">
                        <a href="#tab-profile">Profile</a>
                    </li>
                    <li data-ave-tab-target="#tab-messages">
                        <a href="#tab-messages">Messages</a>
                    </li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="tab-content">
                    <div id="tab-home" class="tab-pane active" data-ave-tab-pane>
                        <h4>Home Tab</h4>
                        <p>This is the home tab content. It is active by default. The JavaScript module finds the root via `data-ave-tabs-root`, the navigation via `data-ave-tabs="nav"`, and then matches targets (`data-ave-tab-target`) with panes (`data-ave-tab-pane`).</p>
                    </div>
                    <div id="tab-profile" class="tab-pane" data-ave-tab-pane>
                        <h4>Profile Tab</h4>
                        <p>This is the profile tab content. Use it to display user information.</p>
                    </div>
                    <div id="tab-messages" class="tab-pane" data-ave-tab-pane>
                        <h4>Messages Tab</h4>
                        <p>This is the messages tab content. List recent communications here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">Modals & Toasts</h3>
            </div>
            <div class="panel-body">
                <p>Trigger interactive overlays like modals and toast notifications using the correct data attributes.</p>
                
                <button type="button" class="btn btn-primary" data-ave-modal-trigger="#demo-modal">
                    Launch Demo Modal
                </button>
                
                <button type="button" class="btn btn-success" data-ave-toast-trigger="success" data-ave-toast-message="The operation was completed successfully!">
                    Show Success Toast
                </button>

                <button type="button" class="btn btn-danger" data-ave-toast-trigger="danger" data-ave-toast-message="An error occurred while processing the request.">
                    Show Error Toast
                </button>
                <p class="text-muted small">Note: A `div#toast-container` must exist in the main layout for toasts to appear.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="demo-modal" class="modal" data-ave-modal>
    <div class="modal-background" data-ave-modal-close></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Demo Modal</h4>
                <button class="close" data-ave-modal-dismiss type="button">&times;</button>
            </div>
            <div class="modal-body">
                <p>This is the body of the modal. The trigger uses `data-ave-modal-trigger` with a selector. The modal container uses `data-ave-modal`. Closing elements use `data-ave-modal-dismiss` or `data-ave-modal-close`.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-ave-modal-dismiss type="button">Close</button>
                <button class="btn btn-primary" type="button">Save Changes</button>
            </div>
        </div>
    </div>
</div>

