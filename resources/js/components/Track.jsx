import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { Steps } from 'rsuite';
import axios from 'axios';
import moment from 'moment';
import '../../css/rsuit.css';
import swal from 'sweetalert';

function TrackingDetails({ packageId, packageZipCode, listDetails }) {
  return (
    <div className="tracking-details">
      <h5>PACKAGE ID: {packageId} / DELIVERY ZIP CODE: {packageZipCode}</h5>
      {listDetails.map((item, i) => (
        <div key={i}>
          <p>{moment(item.created_at).format('LLLL')}</p>
          <p>Status: {item.status}</p>
        </div>
      ))}
    </div>
  );
}

function TrackingSteps({ step, onholdDesc, inboundDesc, dispatchDesc, deliveryDesc, isMobile }) {
  return (
    <div className={isMobile ? "vertical-steps" : "tracking-steps"}>
      <Steps current={step}>
        <Steps.Item title="In Fulfillment" description={onholdDesc} />
        <Steps.Item title="Inbound" description={inboundDesc} />
        <Steps.Item title="Out for Delivery" description={dispatchDesc} />
        <Steps.Item title="Delivery" description={deliveryDesc} />
      </Steps>
    </div>
  );
}

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
    setSearchFieldChanged(false);

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

    listDetails.forEach((item) => {
      if (item.status === 'Manifest') {
        setOnholdDesc(moment(item.created_at).format('LL'));
      }
      if (item.status === 'Inbound') {
        setInboundDesc(moment(item.created_at).format('LL'));
      }
      if (item.status === 'Dispatch') {
        setDispatchDesc(moment(item.created_at).format('LL'));
      }
      if (item.status === 'Delivery') {
        setDeliveryDesc(moment(item.created_at).format('LL'));
      }
    });

    finalStep = listDetails.find((item) => item.status === 'Delivery');
    if (!finalStep) {
      finalStep = listDetails.find((item) => item.status === 'Dispatch');

      if (!finalStep) {
        finalStep = listDetails.find((item) => item.status === 'Inbound');
      }

      if (!finalStep) {
        finalStep = listDetails.find((item) => item.status === 'Manifest');
      }
    }

    if (finalStep) {
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

  const handleSearchFieldChange = (e) => {
    setPackageId(e.target.value);
    setSearchFieldChanged(true);
  }

  // Detectar el tamaño de la pantalla
  const isMobile = window.innerWidth <= 768; // Por ejemplo, considerando 768px como el límite para dispositivos móviles

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

      {searchClicked && !searchFieldChanged && listDetails.length > 0 && (
        <div className="container">
          <div className="row">
            <div className={`col-${isMobile ? '12' : '6'}`}>
              <h6 className="pt-4">Tracking details</h6>
              <hr />
              <TrackingDetails packageId={packageId} packageZipCode={packageZipCode} listDetails={listDetails} />
            </div>
            <div className={`col-${isMobile ? '12' : '6'}`}>
              <h6 className="pt-4">Tracking Steps</h6>
              <TrackingSteps
                step={step}
                onholdDesc={onholdDesc}
                inboundDesc={inboundDesc}
                dispatchDesc={dispatchDesc}
                deliveryDesc={deliveryDesc}
                isMobile={isMobile}
              />
            </div>
          </div>
        </div>
      )}
    </section>
  );
}

export default Track;

// Renderizar el componente en el DOM
if (document.getElementById('tracks')) {
  ReactDOM.render(<Track />, document.getElementById('tracks'));
}
