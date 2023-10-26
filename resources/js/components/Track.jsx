import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import axios from 'axios';
import moment from 'moment';
import '../../css/rsuit.css';
import swal from 'sweetalert';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Steps } from 'antd';

function Track() {
    const [packageId, setPackageId] = useState('');
    const [packageZipCode, setPackageZipCode] = useState('');
    const [listDetails, setListDetails] = useState([]);
    const [step, setStep] = useState(null);
    const [onholdDesc, setOnholdDesc] = useState('');
    const [inboundDesc, setInboundDesc] = useState('');
    const [dispatchDesc, setDispatchDesc] = useState('');
    const [deliveryDesc, setDeliveryDesc] = useState('');
    const [showDetails, setDisplayDetails] = useState(false);

    useEffect(() => {
        handleStep();
    }, [listDetails]);

    useEffect(() => {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const textSearch = urlParams.get('textSearch');

        if(textSearch) {
            setPackageId(textSearch);
            fetchDetails(textSearch);
        }
    }, []);

    const fetchDetails = (searchValue) => {
        const url = url_general + 'trackpackage/detail/' + searchValue;
        axios.get(url)
            .then((response) => {
                setListDetails(response.data.details);
                setPackageZipCode(response.data.details[0].Dropoff_Contact_Name);
                setDisplayDetails(true);
            })
            .catch(() => {
                swal({
                    title: 'Error',
                    text: 'Package was not found',
                    icon: 'error',
                    buttons: {
                        confirm: {
                            text: 'OK',
                            className: 'custom-button'
                        }
                    }
                });
                setDisplayDetails(false);
            });
    }

    const getDetail = (e) => {
        e.preventDefault();
        fetchDetails(packageId);
    }

    const handleStep = () => {
        let finalStep = null;
        setOnholdDesc('');
        setInboundDesc('');
        setDeliveryDesc('');
        setDispatchDesc('');

        listDetails.forEach((item) => {
            if (item.status === 'Manifest') setOnholdDesc(moment(item.created_at).format('LL'));
            if (item.status === 'Inbound') setInboundDesc(moment(item.created_at).format('LL'));
            if (item.status === 'Dispatch') setDispatchDesc(moment(item.created_at).format('LL'));
            if (item.status === 'Delivery') setDeliveryDesc(moment(item.created_at).format('LL'));
        });

        const findStep = status => listDetails.find(item => item.status === status);

        if(findStep('Delivery')) finalStep = 'Delivery';
        else if(findStep('Dispatch')) finalStep = 'Dispatch';
        else if(findStep('Inbound')) finalStep = 'Inbound';
        else if(findStep('Manifest')) finalStep = 'Manifest';

        if (finalStep) {
            const stepMap = {
                'Manifest': 0,
                'Inbound': 1,
                'Dispatch': 2,
                'Delivery': 3
            };
            setStep(stepMap[finalStep]);
        }
    }

    const handleSearchFieldChange = (e) => {
        setPackageId(e.target.value);
        setDisplayDetails(false);
    }

    return (
        <><style>
            {`
                .custom-button {
                    background-color: #015E7C !important;
                    border-color: #015E7C !important;
                }

                .custom-steps .ant-steps-item-finish .ant-steps-item-icon {
                    background-color: #0099CC !important;
                    border-color: #0099CC !important;
                }

                .custom-steps .ant-steps-item-finish .ant-steps-item-tail::before {
                    background-color: #0099CC !important;
                }

                .custom-steps .ant-steps-item-process .ant-steps-item-tail::before {
                    background-color: #0099CC !important;
                }

                .custom-steps .ant-steps-item-process .ant-steps-item-icon {
                    background-color: #0099CC !important;
                    border-color: #0099CC !important;
                }

                .custom-steps .ant-steps-item-finish .ant-steps-item-icon > .ant-steps-icon {
                    color: white !important;
                }
            `}
        </style><section className="section">
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
                                        onChange={handleSearchFieldChange} />
                                </div>
                                <div className="form-group">
                                    <button className="btn btn-primary" type="submit" style={{ backgroundColor: "#015E7C", borderColor: "#015E7C" }}>Search</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                {showDetails && (
                    <div className="container">
                        <div className="row">
                            <div className="col-lg-12">
                                <h6 className="pt-4">Tracking Details</h6>
                                <hr />
                                <h5 className="text-center">
                                    PACKAGE ID: {packageId} / OWNER: {packageZipCode}
                                </h5>
                                <div className={`col-12 mt-2 tracking-details d-none d-md-block`}>
                                    <Steps className="custom-steps" current={step}>
                                        <Steps.Item title="In Fulfillment" description={onholdDesc} />
                                        <Steps.Item title="Inbound" description={inboundDesc} />
                                        <Steps.Item title="Out for Delivery" description={dispatchDesc} />
                                        <Steps.Item title="Delivery" description={deliveryDesc} />
                                    </Steps>
                                </div>
                                <div className="col-12 mt-2 tracking-details  d-block d-sm-none">
                                    <Steps className="custom-steps" current={step} direction="vertical">
                                        <Steps.Item title="In Fulfillment" description={onholdDesc} />
                                        <Steps.Item title="Inbound" description={inboundDesc} />
                                        <Steps.Item title="Out for Delivery" description={dispatchDesc} />
                                        <Steps.Item title="Delivery" description={deliveryDesc} />
                                    </Steps>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </section></>
    );
}

export default Track;

if (document.getElementById('tracks')) {
    ReactDOM.render(<Track />, document.getElementById('tracks'));
}
