import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function PackageCheck() {

    const [listPackageCheck, setListPackageCheck] = useState([]);
    const [listPackageTotal, setListPackageTotal]     = useState([]);
    const [listState , setListState]                  = useState([]);

    const [packageNumber, setPackageNumber] = useState('');
    const [packageDriver, setPackageDriver] = useState('');

    const [listRoute, setListRoute]     = useState([]);

    const [quantityInbound, setQuantityInbound] = useState(0);

    const [Reference_Number_1, setNumberPackage] = useState('');

    const [textMessage, setTextMessage]         = useState('');
    const [textMessageDate, setTextMessageDate] = useState('');
    const [typeMessage, setTypeMessage]         = useState('');

    const [listInbound, setListInbound] = useState([]);

    const [file, setFile]             = useState('');

    const [displayButton, setDisplayButton] = useState('none');

    const [disabledInput, setDisabledInput] = useState(false);

    const [readInput, setReadInput] = useState(false);

    const [dataView, setDataView] = useState('today');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    useEffect(() => {

        if(String(file) == 'undefined' || file == '')
        {
            setViewButtonSave('none');
        }
        else
        {
            setViewButtonSave('block');
        }

    }, [file]);

    const handlerImport = (e) => {

        e.preventDefault();

        setListPackageCheck([]);

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-check/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setListPackageCheck(response.packageList);

                swal("Se importó el archivo!", {

                    icon: "success",
                });

                document.getElementById('fileImport').value = '';

                setViewButtonSave('none');

                LoadingHide();
            },
        );
    }

    const listPackageTable = listPackageCheck.map( (pack, i) => {

        return (

            <tr key={i} className="alert-success">
                <td>{ pack.package }</td>
                <td>{ pack.stop }</td>
                <td>{ pack.driver }</td>
            </tr>
        );
    });

    const onBtnClickFile = () => {

        setViewButtonSave('none');
        
        inputFileRef.current.click();
    }

    const handlerInsert = (e) => {

        e.preventDefault();

        let validation = false;

        listPackageCheck.map( (pack, i) => {

            if(pack.package == Reference_Number_1.trim())
            {
                validation = true;

                setPackageNumber('STOP: '+ pack.stop );
                setPackageDriver('DRIVER: '+ pack.driver);
                setTypeMessage('text-success');

                document.getElementById('soundPitidoSuccess').play();
            }
        });

        if(!validation)
        {
            setPackageNumber('PACKAGE: '+ Reference_Number_1);
            setPackageDriver('NOT EXISTS');
            setTypeMessage('text-danger');

            document.getElementById('soundPitidoWarning').play();
        }

        setNumberPackage('');
        document.getElementById('Reference_Number_1').focus();
    }

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-10 form-group">
                                        <form onSubmit={ handlerInsert } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="">PACKAGE ID</label>
                                                <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } readOnly={ readInput } maxLength="15" required/>
                                            </div>
                                            <div className="col-lg-2 form-group">
                                                <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-2">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <label htmlFor="" style={ {color: 'white'} }>PACKAGE ID</label>
                                                <button type="button" className="btn btn-primary form-control" onClick={ () => onBtnClickFile() }>
                                                    <i className="bx bxs-file"></i> Import
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImport() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div className={ 'col-lg-6 '+ typeMessage}>
                                        <h1>{ packageNumber }</h1>
                                    </div>
                                    <div className={ 'col-lg-6 '+ typeMessage}>
                                        <h1>{ packageDriver }</h1>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead> 
                                            <tr>
                                                <th>PACKAGE #</th>
                                                <th>STOP</th>
                                                <th>DRIVER</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="col-lg-12">
                                <Pagination
                                    activePage={page}
                                    totalItemsCount={totalPackage}
                                    itemsCountPerPage={totalPage}
                                    onChange={(pageNumber) => handlerChangePage(pageNumber)}
                                    itemClass="page-item"
                                    linkClass="page-link"
                                    firstPageText="First"
                                    lastPageText="Last"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PackageCheck;

// DOM element
if (document.getElementById('packageCheck')) {
    ReactDOM.render(<PackageCheck />, document.getElementById('packageCheck'));
}