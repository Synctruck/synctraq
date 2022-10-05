import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import axios from 'axios';
import moment from 'moment';
import { Steps } from 'rsuite';
import '../../css/rsuit.css';

function Track() {

    const [packageId, setPackageId]         = useState('');
    const [packageClient, setPackageClient] = useState('');

    const [listDetails, setListDetails] = useState([]);
    const [step, setStep] = useState(null);

    const [onholdDesc, setOnholdDesc] = useState('');
    const [inboundDesc, setInboundDesc] = useState('');
    const [dispatchDesc, setDispatchDesc] = useState('');
    const [deliveryDesc, setDeliveryDesc] = useState('');

    useEffect(() => {
        handleStep();
    }, [listDetails])



    const getDetail = (e) => {
        e.preventDefault();
        console.log('submit');

        let url = url_general +'track/detail/'+packageId
        let method = 'GET'

        axios({
            method: method,
            url: url
        })
        .then((response) => {

            console.log(response.data);
            setListDetails(response.data.details);
            setPackageClient(response.data.details[0].Dropoff_Contact_Name);
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
            if(item.status == 'On hold'){
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
                    return item.status =='On hold'
                });
            }
        }

        if(finalStep){
            console.log('final step: ',finalStep.status);

            switch (finalStep.status) {
                case 'On hold':
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
                        <h5 className="card-title text-center pb-0 fs-4">Order tracking</h5>
                        <p className="text-center"><span> NOTE: Package ID is the entire package identifier under the barcode on your package. Package ID Example: 222668400492 </span></p><br></br>
                        <div className="col-lg-12">
                            <form onSubmit={getDetail}>
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
                                    <button className='btn btn-warning text-white' type='submit'> Search</button>
                                </div>
                            </form>
                        </div>


                        <h6 className="pt-4">Traking details </h6><hr />
                        <div className='row'>
                             <h5 className='text-center'>PACKAGE ID: {packageId}  / CLIENT: { packageClient }</h5>
                            <div className='col-12 mt-2'>
                                <Steps current={step}>
                                    <Steps.Item title="On hold" description={onholdDesc} />
                                    <Steps.Item title="Inbound" description={inboundDesc}/>
                                    <Steps.Item title="Out of Delivery" description={dispatchDesc}/>
                                    <Steps.Item title="Delivery" description={deliveryDesc}/>
                                </Steps>
                            </div>
                        </div>
                        {/* <div className="col-lg-6">

                            <table className='table'>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    { detailsListTable }
                                </tbody>
                            </table>
                        </div> */}
                    </div>
                </div>
            </div>
        </section>
    );
}

export default Track;

// DOM element
if (document.getElementById('track')) {
    ReactDOM.render(<Track />, document.getElementById('track'));
}
