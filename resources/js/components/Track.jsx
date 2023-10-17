import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import axios from 'axios';
import moment from 'moment';
import { Steps } from 'rsuite';
import '../../css/rsuit.css';

function Track() {
    const [packageId, setPackageId] = useState('');
    const [packageZipCode, setPackageZipCode] = useState('');
    const [listDetails, setListDetails] = useState([]);
    const [step, setStep] = useState(null);
    const [onholdDesc, setOnholdDesc] = useState('');
    const [inboundDesc, setInboundDesc] = useState('');
    const [dispatchDesc, setDispatchDesc] = useState('');
    const [deliveryDesc, setDeliveryDesc] = useState('');
    const [searchClicked, setSearchClicked] = useState(false);
    const [errorAlert, setErrorAlert] = useState(false); // Variable para rastrear si hubo un error en la solicitud
    const [errorText, setErrorText] = useState(''); // Mensaje de error

    useEffect(() => {
        handleStep();
    }, [listDetails]);

    const getDetail = (e) => {
        e.preventDefault();
        setSearchClicked(true);

        let url = url_general + 'trackpackage/detail/' + packageId;
        let method = 'GET';

        axios({
            method: method,
            url: url
        })
        .then((response) => {
            setListDetails(response.data.details);
            setPackageZipCode(response.data.details[0].Dropoff_Contact_Name);
            setErrorAlert(false); // Reiniciar la alerta de error
        })
        .catch(function (error) {
            setErrorAlert(true); // Marcar que hubo un error en la solicitud
            setErrorText('Error: ' + error.message); // Establecer el mensaje de error
            console.error('Error:', error);
        })
        .finally();
    }

    const handleStep = () => {
        // Verificar si hubo un error, y si lo hubo, no mostrar los "Tracking details"
        if (errorAlert) {
            return;
        }

        // Resto del código para manejar los "Tracking details"
    }

    return (
        <section className="section">
            <div className="container">
                <div className="row">
                    <div className="col-lg-6">
                        <form id="formSearch" onSubmit={getDetail}>
                            <div className="form-group">
                                <input
                                    type="text"
                                    id="textSearch"
                                    className="form-control"
                                    placeholder="Package ID"
                                    required
                                    value={packageId}
                                    onChange={(e) => setPackageId(e.target.value)}
                                />
                            </div>
                            <div className="form-group">
                                <button className="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {searchClicked && (
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12">
                            {errorAlert ? (
                                <div className="alert alert-danger">
                                    {errorText}
                                </div>
                            ) : (
                                <>
                                    <h6 className="pt-4">Tracking details</h6>
                                    <hr />
                                    <h5 className="text-center">PACKAGE ID: {packageId}  / DELIVERY ZIP CODE: {packageZipCode}</h5>
                                    <div className="col-12 mt-2">
                                        <Steps current={step}>
                                            <Steps.Item title="In Fulfillment" description={onholdDesc} />
                                            <Steps.Item title="Inbound" description={inboundDesc} />
                                            <Steps.Item title="Out for Delivery" description={dispatchDesc} />
                                            <Steps.Item title="Delivery" description={deliveryDesc} />
                                        </Steps>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}

export default Track;

// DOM element
if (document.getElementById('tracks')) {
    ReactDOM.render(<Track />, document.getElementById('tracks'));
}
