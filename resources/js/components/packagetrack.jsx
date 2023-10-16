import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import axios from 'axios';
import moment from 'moment';
import { Steps } from 'rsuite';
import '../../css/rsuit.css';

function PackageTrack() {

    const [packageId, setPackageId]         = useState('');
    const [packageZipCode, setPackageZipCode] = useState('');

    const [listDetails, setListDetails] = useState([]);
    const [step, setStep] = useState(null);

    const [onholdDesc, setOnholdDesc] = useState('');
    const [inboundDesc, setInboundDesc] = useState('');
    const [dispatchDesc, setDispatchDesc] = useState('');
    const [deliveryDesc, setDeliveryDesc] = useState('');

    useEffect(() => {
        handleStep();
    }, [listDetails])

    useEffect( () => {

        if(textSearch != '')
        {
            setPackageId(textSearch);

            history.pushState(null, "", "track-detail?textSearch="+ textSearch);

            console.log('submit');

            let url    = url_general +'track/detail/'+ textSearch;
            let method = 'GET'

            axios({
                method: method,
                url: url
            })
            .then((response) => {

                console.log(response.data);
                setListDetails(response.data.details);
                setPackageZipCode(response.data.details[0].Dropoff_Postal_Code);
            })
            .catch(function(error) {
               alert('Error:',error);
            })
            .finally();
        }

    }, []);

    const getDetail = (e) => {

        history.pushState(null, "", "track-detail?textSearch="+ packageId);

        e.preventDefault();

        console.log('submit');

        let url    = url_general +'track/detail/'+ packageId;
        let method = 'GET'

        axios({
            method: method,
            url: url
        })
        .then((response) => {

            console.log(response.data);
            setListDetails(response.data.details);
            setPackageZipCode(response.data.details[0].Dropoff_Contact_Name);
        })
        .catch(function(error) {
           alert('Error:',error);
        })
        .finally();
    }

    const handleStep =() => {
        console.log('cambiando step');
        let finalStep = null;
        setOnholdDesc('');
        setInboundDesc('');
        setDeliveryDesc('');
        setDispatchDesc('');

        listDetails.map((item,i) => {
            if(item.status == 'Manifest'){
                setOnholdDesc(moment(item.created_at).format('LL'))
            }
            if(item.status == 'Inbound'){
                setInboundDesc(moment(item.created_at).format('LL'))
            }
            if(item.status == 'Dispatch'){
                setDispatchDesc(moment(item.created_at).format('LL'))
            }
            if(item.status == 'Delivery'){
                setDeliveryDesc(moment(item.created_at).format('LL'))
            }
        });



        finalStep = listDetails.find(item => {
            return item.status =='Delivery'
        });
        if(! finalStep){
            finalStep = listDetails.find(item => {
                return item.status =='Dispatch'
            });

            if(! finalStep){
                finalStep = listDetails.find(item => {
                    return item.status =='Inbound'
                });
            }

            if(! finalStep){
                finalStep = listDetails.find(item => {
                    return item.status =='Manifest'
                });
            }
        }

        if(finalStep){
            console.log('final step: ',finalStep.status);

            switch (finalStep.status) {
                case 'Manifest':
                    setStep(0);
                    break;
                case 'Inbound':
                    setStep(1);
                    break;
                case 'Dispatch':
                    setStep(2);
                    break;
                case 'Delivery':
                    setStep(3);
                    break;
                default:
                    break;
            }
        }
    }

    const detailsListTable = listDetails.map( (item, i) => {

        return (

            <tr key={i}>
                <td>{ moment(item.created_at).format('LLLL') }</td>
                <td>{ item.status }</td>
            </tr>
        );
    });



    return (

        <section className="section">
            <div className="card mb-3">
                <div className="card-body">
                    <div className=" pb-2">
                        <div className="row">
                            <div className="col-lg-12 text-center">
                                <div className="form-group">
                                    <img src="./img/logo.png" alt="" width="220"/>
                                </div>
                            </div>
                            <div className="col-lg-12">
                                <h5 className="card-title text-center pb-0 fs-4">Track Your Package</h5>
                                <p className="text-center"><span> <b>NOTE:</b> Package ID is the entire package identifier under the barcode on your package. Package ID Example: 222668400492 </span></p><br></br>
                            </div>
                            <div className="col-lg-12">
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
                                            /><br />
                                        <button className='btn' type='submit' style={{ backgroundColor: '#015E7C', color: 'white' }}> Search</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <h6 className="pt-4">Tracking details </h6><hr />
                        <div className='row'>
                            <h5 className='text-center'>PACKAGE ID: {packageId}  / DELIVERY ZIP CODE: { packageZipCode }</h5>
                            <div className='col-12 mt-2'>
                                <Steps current={step}>
                                    <Steps.Item title="In Fulfillment" description={onholdDesc} />
                                    <Steps.Item title="Inbound" description={inboundDesc}/>
                                    <Steps.Item title="Out for Delivery" description={dispatchDesc}/>
                                    <Steps.Item title="Delivery" description={deliveryDesc}/>
                                </Steps>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="card mb-3" >
                <div className="card-body">
                    <div className=" pb-2">
                        <div className="row">
                            <div className="col-lg-6">
                                <div className="col-lg-12">
                                    <div className="form-group">
                                        <h5 className="card-title text-center pb-0 fs-4">Need More Help</h5>
                                    </div>
                                </div>
                                <div className="col-lg-12 text-center">
                                    <div className="form-group">
                                        <h5 className='text-center'>Customer Support</h5>
                                    </div>
                                    <div className="form-group">
                                        <p>(551) 225-0007</p>
                                    </div>
                                    <div className="form-group">
                                        <p>connect@synctruck.com</p>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-6">
                                <div className="col-lg-12">
                                    <div className="form-group">
                                        <h5 className="card-title text-center pb-0 fs-4">Office Hours</h5>
                                    </div>
                                </div>
                                <div className="col-lg-12 text-center">
                                    <div className="form-group">
                                        <h5 className='text-center'>Mon – Fri</h5>
                                    </div>
                                    <div className="form-group">
                                        <p>09:00 am – 05:00 pm</p>
                                    </div>
                                    <div className="form-group">
                                        <p>Sat – Sun | Closed</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PackageTrack;

// DOM element
if (document.getElementById('track')) {
    ReactDOM.render(<PackageTrack />, document.getElementById('track'));
}
