import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'

function Home() {

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12 text-center"> 
                                        <h3>Bienvenido al Sistema</h3>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group">
                                <div className="col-lg-12 text-center">
                                   <img src="./img/welcome.jpg" alt="No hay imagen"/> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default Home;

// DOM element
if (document.getElementById('home')) {
    ReactDOM.render(<Home />, document.getElementById('home'));
}