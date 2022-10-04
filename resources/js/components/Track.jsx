import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import axios from 'axios';
import moment from 'moment'

function Track() {

    const [packageId, setPackageId] = useState('');
    const [listDetails, setListDetails] = useState([]);

    useEffect(() => {

        // listAllUser(page);

    }, [])



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
        })
        .catch(function(error) {
           alert('Error:',error);
        })
        .finally();
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
                        <p className="text-center small">NOTE: Package ID is the entire package identifier under the barcode on your package. Package ID Example: 222668400492</p>
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
                        <h6 className="pt-4">Traking details </h6><hr />traking details
                        <div className="col-lg-6">

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
                        </div>
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
