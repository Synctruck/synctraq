import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import Pagination from "react-js-pagination";
import axios from 'axios';
import moment from 'moment';
import { Steps } from 'rsuite';
import '../../css/rsuit.css';
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
    const [searchClicked, setSearchClicked] = useState(false); // Variable para rastrear si se hizo clic en Search

    useEffect(() => {
        handleStep();
    }, [listDetails]);

    useEffect(() => {
        if (packageId !== '' && searchClicked) { 
            history.pushState(null, "", "trackpackage-detail?textSearch=" + packageId);

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
                setPackageZipCode(response.data.details[0].Dropoff_Postal_Code);
            })
            .catch(function (error) {
                alert('Error:', error);
            })
            .finally();
        }
    }, [packageId, searchClicked]);

    const getDetail = (e) => {
        e.preventDefault();
        setSearchClicked(true); // Marcar que se hizo clic en Search

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
        .catch(function (error) {
            alert('Error:', error);
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
          {searchClicked && listDetails.length > 0 && (
  <div className="container">
    <div className="row">
      <div className="col-lg-12">
        <h6 className="pt-4">Tracking details</h6>
        <hr />
        <h5 className="text-center">PACKAGE ID: {packageId}  / DELIVERY ZIP CODE: {packageZipCode}</h5>
        <div className="col-12 mt-2 tracking-details" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
          <Steps current={step}>
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