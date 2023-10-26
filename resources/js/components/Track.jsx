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
    const [searchClicked, setSearchClicked] = useState(false);
    const [searchFieldChanged, setSearchFieldChanged] = useState(false);

    useEffect(() => {
        handleStep();
    }, [listDetails]);

    
    useEffect( () => {

        if(textSearch != '')
        {
            setPackageId(textSearch);

            history.pushState(null, "", "trackpackage-detail?textSearch="+ textSearch);

            console.log('submit');

            let url    = url_general +'trackpackage/detail/'+ textSearch;
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

        history.pushState(null, "", "trackpackage-detail?textSearch="+ packageId);
        e.preventDefault();
        setSearchClicked(true);
        setSearchFieldChanged(false); // Reiniciar el estado de búsqueda del campo

        console.log('submit');

        let url = url_general + 'trackpackage/detail/' + packageId;
        let method = 'GET';

        axios({
            method: method,
            url: url
        })
        .then((response) => {
            console.log(response.data);
            setListDetails(response.data.details);
            setPackageZipCode(response.data.details[0].Dropoff_Contact_Name);
        })
        .catch(function () {
            swal('Error', 'Package was not found', 'error');
        })
        .finally();
    }

    const handleStep = () => {
        console.log('cambiando step');
        let finalStep = null;
        setOnholdDesc('');
        setInboundDesc('');
        setDeliveryDesc('');
        setDispatchDesc('');

        listDetails.map((item, i) => {
            if (item.status == 'Manifest') {
                setOnholdDesc(moment(item.created_at).format('LL'));
            }
            if (item.status == 'Inbound') {
                setInboundDesc(moment(item.created_at).format('LL'));
            }
            if (item.status == 'Dispatch') {
                setDispatchDesc(moment(item.created_at).format('LL'));
            }
            if (item.status == 'Delivery') {
                setDeliveryDesc(moment(item.created_at).format('LL'));
            }
        });

        finalStep = listDetails.find((item) => {
            return item.status == 'Delivery';
        });
        if (!finalStep) {
            finalStep = listDetails.find((item) => {
                return item.status == 'Dispatch';
            });

            if (!finalStep) {
                finalStep = listDetails.find((item) => {
                    return item.status == 'Inbound';
                });
            }

            if (!finalStep) {
                finalStep = listDetails.find((item) => {
                    return item.status == 'Manifest';
                });
            }
        }

        if (finalStep) {
            console.log('final step: ', finalStep.status);

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

    const detailsListTable = listDetails.map((item, i) => {
        return (
            <tr key={i}>
                <td>{moment(item.created_at).format('LLLL')}</td>
                <td>{item.status}</td>
            </tr>
        );
    });

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
          
            {listDetails.length > 0 &&  !searchFieldChanged &&(
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
                                <Steps current={step} direction="vertical">
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

            </section>
          );
          

}

export default Track;

// DOM element
if (document.getElementById('tracks')) {
    ReactDOM.render(<Track />, document.getElementById('tracks'));
}

