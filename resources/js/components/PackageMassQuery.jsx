import React, { useState, useEffect, useRef } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import ReactLoading from 'react-loading';
import { DownloadTableExcel } from 'react-export-table-to-excel';

function PackageMassQuery() {

    const [listPackageCheck, setListPackageCheck] = useState([]);
    const [listPackageTotal, setListPackageTotal]     = useState([]);
    const [listState , setListState]                  = useState([]);

    const [packageNumber, setPackageNumber] = useState('');
    const [packageDriver, setPackageDriver] = useState('');

    const [listRoute, setListRoute]     = useState([]);

    const tableRef = useRef(null);

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
    const [isLoading, setIsLoading]       = useState(false);

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
        setViewButtonSave('none');

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setIsLoading(true);

        fetch(url_general +'report/mass-query/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setListPackageCheck(response.listAll);
                setQuantityInbound(response.listAll.length);

                swal("Se importó el archivo!", {

                    icon: "success",
                });

                document.getElementById('fileImport').value = '';

                setViewButtonSave('none');

                setIsLoading(false);
            },
        );
    }

    const listPackageTable = listPackageCheck.map( (packageInbound, i) => {

        return (

            <tr key={i}>
                <td>
                    { packageInbound.created_at.substring(5, 7) }-{ packageInbound.created_at.substring(8, 10) }-{ packageInbound.created_at.substring(0, 4) }
                </td>
                <td><b>{ packageInbound.company }</b></td>
                <td><b>{ packageInbound.Reference_Number_1 }</b></td>
                <td>{ packageInbound.status }</td>
                <td>
                    { packageInbound.statusDate.substring(5, 7) }-{ packageInbound.statusDate.substring(8, 10) }-{ packageInbound.statusDate.substring(0, 4) }
                </td>
                <td>{ packageInbound.statusDescription }</td>
                <td>{ packageInbound.Dropoff_Contact_Name }</td>
                <td>{ packageInbound.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageInbound.Dropoff_Address_Line_1 }</td>
                <td>{ packageInbound.Dropoff_City }</td>
                <td>{ packageInbound.Dropoff_Province }</td>
                <td>{ packageInbound.Dropoff_Postal_Code }</td>
                <td>{ packageInbound.Route }</td>
                <td>{ packageInbound.Weight }</td>
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
                                    <div className="col-lg-10 form-group" style={ {display: 'none'} }>
                                        <form onSubmit={ handlerInsert } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="">PACKAGE ID</label>
                                                <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } readOnly={ readInput } maxLength="20" required/>
                                            </div>
                                            <div className="col-lg-2 form-group">
                                                <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-2 form-group">
                                        <DownloadTableExcel
                                            filename="Report Mass Query"
                                            sheet="users"
                                            currentTableRef={tableRef.current}
                                        >
                                            <button className="btn btn-success btn-sm form-control">
                                                <i className="ri-file-excel-fill"></i> EXPORT
                                            </button>
                                        </DownloadTableExcel>
                                    </div>
                                    <div className="col-lg-2">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
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
                                            <div className="form-group" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                                {
                                                    (
                                                        isLoading
                                                        ? 
                                                            <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                        :
                                                            ''
                                                    )
                                                }
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div className="row form-group">
                                    <div className="col-lg-2 form-group">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Packages: { quantityInbound }</b>
                                    </div>
                                </div>

                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table ref={ tableRef } className="table table-hover table-condensed table-bordered">
                                        <thead> 
                                            <tr>
                                                <th>DATE</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>ACTUAL STATUS</th>
                                                <th>STATUS DATE</th>
                                                <th>STATUS DESCRIPTION</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP C</th>
                                                <th>ROUTE</th>
                                                <th>WEIGHT</th>
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

export default PackageMassQuery;

// DOM element
if (document.getElementById('packageMassQuery')) {
    ReactDOM.render(<PackageMassQuery />, document.getElementById('packageMassQuery'));
}