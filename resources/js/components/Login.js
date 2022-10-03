import React, { useState } from 'react';
import ReactDOM from 'react-dom';

// import logo from './../../../public/img/logo.PNG'

function Login() {

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const handlerSubmit = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('email', email);
        formData.append('password', password);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'user/login', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    console.log(response.user);
                    swal("Correct data!", {

                        icon: "success",
                    });
                    setTimeout( () => {
                        if(response.user.idRole == 3 || response.user.idRole == 4){
                            location.href = '/profile'

                        }else{
                            location.href = './home'
                        }

                    }, 1500);
                }
                else
                {
                    swal("wrong credentials or blocked user", {

                        icon: "error"
                    });
                }

                LoadingHide();
            },
        );
    }

    return (
        <div className="container">

          <section className="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
            <div className="container">
              <div className="row justify-content-center">
                <div className="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                  <div className="d-flex justify-content-center py-4">
                    {/* <img src={logo} /> */}
                  </div>

                  <div className="card mb-3">

                    <div className="card-body">

                      <div className="pt-4 pb-2">
                        <h5 className="card-title text-center pb-0 fs-4">Login</h5>
                        <p className="text-center small">Enter your email and password</p>
                      </div>

                        <form onSubmit={ handlerSubmit } className="row g-3 needs-validation">

                            <div className="col-12">
                              <label for="yourUsername" className="form-label">Email</label>
                              <input type="email" onChange={ (e) => setEmail(e.target.value) } className="form-control" id="yourUsername" required/>
                            </div>

                            <div className="col-12">
                              <label for="yourPassword" className="form-label">Password</label>
                              <input type="password" onChange={ (e) => setPassword(e.target.value) } className="form-control" id="yourPassword" required/>
                              <div className="invalid-feedback">Please enter your password!</div>
                            </div>

                            <div className="col-12" style={ {display: 'none'} }>
                              <div className="form-check">
                                <input className="form-check-input" type="checkbox" name="remember" value="true" id="rememberMe" />
                                <label className="form-check-label" for="rememberMe">Remember me</label>
                              </div>
                            </div>
                            <div className="col-12">
                              <button className="btn btn-primary w-100" type="submit">Sign in</button>
                            </div>
                            <div className="col-12" style={ {display: 'none'} }>
                              <p className="small mb-0">Don't have account? <a href="pages-register.html">Create an account</a></p>
                            </div>
                        </form>
                        <br/>
                        <a href="./home/public">See general information</a><br/><br/>
                        <a href="./package-check" className="text-success">Go to Check Stop</a>
                    </div>
                  </div>


                </div>
              </div>
            </div>

          </section>

        </div>
    );
}

export default Login;

// DOM element
if (document.getElementById('login')) {
    ReactDOM.render(<Login />, document.getElementById('login'));
}
