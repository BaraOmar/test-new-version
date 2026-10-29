<?php

/**
 * Certificate preview card — filled by JS from certificate JSON.
 */
?>
<div class="o_sch_cert_doc" id="cert-card" hidden>
    <div class="o_sch_cert_doc_head">
        <div>
            <div class="o_sch_cert_school" data-cert="school_name"></div>
            <div class="o_sch_cert_tagline" data-cert="school_tagline"></div>
        </div>
        <div class="o_sch_cert_doc_title_wrap">
            <div class="o_sch_cert_doc_title" data-cert="title"></div>
            <div class="o_sch_cert_doc_meta" data-cert="doc_meta"></div>
        </div>
    </div>

    <div class="o_sch_cert_student_box">
        <div class="o_sch_cert_student_main">
            <div class="o_sch_cert_student_name" data-cert="student_name"></div>
            <div class="o_sch_cert_student_class" data-cert="class_label"></div>
        </div>
        <div class="o_sch_cert_student_code" data-cert="student_code_block"></div>
    </div>

    <div class="o_sch_cert_table_wrap">
        <table class="o_sch_cert_table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Marks</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody data-cert="subjects"></tbody>
        </table>
    </div>

    <div class="o_sch_cert_summary">
        <div class="o_sch_cert_metric">
            <span>Aggregate</span>
            <strong data-cert="aggregate_label"></strong>
        </div>
        <div class="o_sch_cert_metric o_sch_cert_metric--accent">
            <span>Average</span>
            <strong data-cert="average_label"></strong>
        </div>
        <div class="o_sch_cert_metric">
            <span>Attendance</span>
            <strong data-cert="attendance_label"></strong>
        </div>
    </div>

    <p class="o_sch_cert_readonly_note" data-cert="readonly_note" hidden>
        This certificate is read-only. Marks are taken from your formal course grades.
    </p>
</div>
