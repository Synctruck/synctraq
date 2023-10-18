import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { Steps } from 'rsuite';
import axios from 'axios';
import moment from 'moment';
import '../../css/rsuit.css';
import swal from 'sweetalert';
import 'bootstrap/dist/css/bootstrap.min.css';

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
    const [searchFieldChanged, setSearchFieldChanged] = useState(false);

    useEffect(() => {
        handleStep();
    }, [listDetails]);

    const getDetail = (e) => {
        e.preventDefault();
        setSearchClicked(true);
        setSearchFieldChanged(false); // Reiniciar el estado de búsqueda del campo

        let url = url_general + 'trackpackage/detail/' + packageId;
        let method = 'GET';

        axios({
            method: method,
            url: url
        })
        .then((response) => {
            setListDetails(response.data.details);
            setPackageZipCode(response.data.details[0].Dropoff_Contact_Name);
        })
        .catch(function () {
            swal('Error', 'Package was not found', 'error');
        });
    }

    const handleStep = () => {
        let finalStep = null;
        setOnholdDesc('');
        setInboundDesc('');
        setDeliveryDesc('');
        setDispatchDesc('');

        if (listDetails.length > 0) {
            finalStep = listDetails[listDetails.length - 1];

            if (finalStep) {
                switch (finalStep.status) {
                    case 'Manifest':
                        setStep(0);
                        setOnholdDesc(moment(finalStep.created_at).format('LL'));
                        break;
                    case 'Inbound':
                        setStep(1);
                        setInboundDesc(moment(finalStep.created_at).format('LL'));
                        break;
                    case 'Dispatch':
                        setStep(2);
                        setDispatchDesc(moment(finalStep.created_at).format('LL'));
                        break;
                    case 'Delivery':
                        setStep(3);
                        setDeliveryDesc(moment(finalStep.created_at).format('LL'));
                        break;
                    default:
                        break;
                }
            }
        }
    }

    const detailsListTable = listDetails.map((item, i) => (
        <tr key={i}>
            <td>{moment(item.created_at).format('LLLL')}</td>
            <td>{item.status}</td>
        </tr>
    ));

    const handleSearchFieldChange = (e) => {
        setPackageId(e.target.value);
        setSearchFieldChanged(true);
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
                                    onChange={handleSearchFieldChange}
                                />
                            </div>
                            <div className="form-group">
                                <button className="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {searchClicked && listDetails.length > 0 && !searchFieldChanged && (
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12">
                            <h6 className="pt-4">Tracking Details</h6>
                            <hr />
                            <h5 className="text-center">
                                PACKAGE ID: {packageId} / OWNER: {packageZipCode}
                            </h5>
                            <div className={`col-12 mt-2 tracking-details d-none d-md-block`}>
                                <Steps current={step}>
                                    <Steps.Item title="In Fulfillment" description={onholdDesc} />
                                    <Steps.Item title="Inbound" description={inboundDesc} />
                                    <Steps.Item title="Out for Delivery" description={dispatchDesc} />
                                    <Steps.Item title="Delivery" description={deliveryDesc} />
                                </Steps>
                            </div>
                            <div className="col-12 mt-2 tracking-details  d-block d-sm-none">
                                <div className="row">
                                    <div className="col-md-3">
                                        <Steps current={step === 0 ? 0 : -1} className="text-center">
                                            <Steps.Item title="In Fulfillment" />
                                        </Steps>
                                    </div>
                                    <div className="col-md-3">
                                        <Steps current={step === 1 ? 0 : -1} className="text-center">
                                            <Steps.Item title="Inbound" />
                                        </Steps>
                                    </div>
                                    <div className="col-md-3">
                                        <Steps current={step === 2 ? 0 : -1} className="text-center">
                                            <Steps.Item title="Out for Delivery" />
                                        </Steps>
                                    </div>
                                    <div className="col-md-3">
                                        <Steps current={step === 3 ? 0 : -1} className="text-center">
                                            <Steps.Item title="Delivery" />
                                        </Steps>
                                    </div>
                                </div>
                            </div>
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

