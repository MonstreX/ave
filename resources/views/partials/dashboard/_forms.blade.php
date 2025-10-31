<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">Forms</h3>
            </div>
            <div class="panel-body">
                <form>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-field">
                                <label for="text-input">Text Input</label>
                                <input type="text" class="form-control" id="text-input" placeholder="Enter text">
                            </div>
                            <div class="form-field">
                                <label for="email-input">Email Address</label>
                                <input type="email" class="form-control" id="email-input" placeholder="name@example.com">
                                <small class="text-muted">We'll never share your email with anyone else.</small>
                            </div>
                            <div class="form-field">
                                <label for="password-input">Password</label>
                                <input type="password" class="form-control" id="password-input" placeholder="Password">
                            </div>
                             <div class="form-field">
                                <label for="custom-file-input">Custom File Input</label>
                                <div class="custom-file-input">
                                    <input type="file" id="custom-file-input">
                                    <span class="custom-file-label">Choose file...</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-field">
                                <label for="select-input">Standard Select</label>
                                <select class="form-control" id="select-input">
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                    <option>Option 3</option>
                                    <option>Option 4</option>
                                    <option>Option 5</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Checkboxes</label>
                                <div class="checkbox">
                                    <label><input type="checkbox" value="">Option 1</label>
                                </div>
                                <div class="checkbox">
                                    <label><input type="checkbox" value="">Option 2</label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Radio Buttons</label>
                                <div class="radio">
                                    <label><input type="radio" name="optradio" checked>Option 1</label>
                                </div>
                                <div class="radio">
                                    <label><input type="radio" name="optradio">Option 2</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-field">
                                <label for="disabled-input">Disabled Input</label>
                                <input type="text" class="form-control" id="disabled-input" placeholder="Disabled input" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="form-field">
                                <label for="readonly-input">Read-only Input</label>
                                <input type="text" class="form-control" id="readonly-input" placeholder="Read-only input" value="This value is not editable" readonly>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h4>Input Groups</h4>
                    <div class="form-field">
                        <div class="input-group">
                            <span class="input-group-addon">@</span>
                            <input type="text" class="form-control" placeholder="Username">
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" class="form-control">
                            <span class="input-group-addon">.00</span>
                        </div>
                    </div>
                    <div class="form-field has-error">
                        <label class="control-label" for="inputError">Input with error</label>
                        <input type="text" class="form-control" id="inputError">
                        <span class="help-block has-error">Example help text with error.</span>
                    </div>
                    <div class="form-field has-success">
                        <label class="control-label" for="inputSuccess">Input with success</label>
                        <input type="text" class="form-control" id="inputSuccess">
                        <span class="help-block has-success">Example help text with success.</span>
                    </div>
                    <hr>
                    <h4>Custom Select Dropdown</h4>
                    <div class="form-field">
                        <label for="custom-select-dropdown">Choose an option</label>
                        <div class="select-dropdown" data-ave-select-dropdown>
                            <button type="button" class="select-dropdown__toggle" data-ave-select-toggle data-placeholder="Select an option">
                                <span class="select-dropdown__label" data-ave-select-label>Select an option</span>
                                <i class="voyager-angle-down select-dropdown__caret"></i>
                            </button>
                            <div class="select-dropdown__menu" data-ave-select-menu>
                                <div class="select-dropdown__search">
                                    <input type="text" placeholder="Search..." class="form-control" data-ave-select-dropdown-search>
                                </div>
                                <ul>
                                    <li class="select-dropdown__option" data-ave-select-option data-value="option1" data-label="Option 1">Option 1</li>
                                    <li class="select-dropdown__option" data-ave-select-option data-value="option2" data-label="Option 2">Option 2</li>
                                    <li class="select-dropdown__option" data-ave-select-option data-value="option3" data-label="Option 3">Option 3</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-default">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

