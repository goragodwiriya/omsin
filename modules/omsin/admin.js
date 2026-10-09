/**
 * modules/omsin/admin.js
 *
 * ลงทะเบียน route และ helper ของโมดูลออมสิน (บัญชีรายรับ-รายจ่าย)
 */
EventManager.on('router:initialized', () => {
  // หน้าแรกของระบบเป็นสรุปรายรับ-รายจ่าย แทน Dashboard เดิม
  RouterManager.register('/', {
    template: 'omsin/dashboard.html',
    title: '{LNG_Income and Expenditure summary}',
    requireAuth: true
  });

  RouterManager.register('/omsin', {
    template: 'omsin/record.html',
    title: '{LNG_Recording} {LNG_Income}/{LNG_Expense}',
    requireAuth: true
  });

  RouterManager.register('/omsin-edit', {
    template: 'omsin/record.html',
    title: '{LNG_Edit}',
    menuPath: '/omsin',
    requireAuth: true
  });

  RouterManager.register('/omsin-report', {
    template: 'omsin/report.html',
    title: '{LNG_Income and Expenditure summary}',
    requireAuth: true
  });

  RouterManager.register('/omsin-daily', {
    template: 'omsin/daily.html',
    title: '{LNG_Daily Report}',
    requireAuth: true
  });

  RouterManager.register('/omsin-search', {
    template: 'omsin/search.html',
    title: '{LNG_Custom Report}',
    requireAuth: true
  });

  RouterManager.register('/omsin-categories', {
    template: 'omsin/categories.html',
    title: '{LNG_Category}',
    requireAuth: true
  });

  RouterManager.register('/omsin-database', {
    template: 'omsin/database.html',
    title: '{LNG_Import}/{LNG_Export}',
    requireAuth: true
  });

  RouterManager.register('/omsin-about', {
    template: 'omsin/about.html',
    title: '{LNG_About}',
    requireAuth: true
  });
});

/**
 * ฟอร์มบันทึกรายรับ-รายจ่าย แสดงเฉพาะช่องที่เกี่ยวข้องกับสิ่งที่ผู้ใช้เลือกทำ
 * (เหมือน initIerecord() ของระบบเดิม) เรียกโดย data-on-load ของฟอร์ม
 *
 * OUT/IN    หมวดหมู่ + กระเป๋าเงิน + หมายเหตุ
 * TRANSFER  กระเป๋าต้นทาง/ปลายทาง
 * INIT      ชื่อกระเป๋าเงินใหม่
 *
 * @param {HTMLElement} form
 *
 * @return {Function} ฟังก์ชันสำหรับถอด event listener
 */
function omsinRecordForm(form) {
  const status = form.querySelector('[name="status"]');
  const idField = form.querySelector('[name="id"]');

  // แก้ไขรายการเดิม ชนิดของรายการเปลี่ยนไม่ได้ และแก้หมายเหตุได้ทุกชนิด
  const isEdit = !!idField && parseInt(idField.value, 10) > 0;

  /**
   * @param {string} name ชื่อกลุ่มช่องกรอกใน data-omsin-group
   * @param {boolean} visible
   */
  const toggle = (name, visible) => {
    form.querySelectorAll('[data-omsin-group="' + name + '"]').forEach(element => {
      element.hidden = !visible;
    });
  };

  const apply = () => {
    const value = status ? status.value : '';
    const isRecord = value === 'IN' || value === 'OUT';

    toggle('category', isRecord);
    toggle('wallet', isRecord);
    toggle('comment', isRecord || isEdit);
    toggle('transfer', !isEdit && value === 'TRANSFER');
    toggle('wallet_name', value === 'INIT');
  };

  if (status) {
    status.addEventListener('change', apply);
  }
  apply();

  return () => {
    if (status) {
      status.removeEventListener('change', apply);
    }
  };
}

/**
 * ดาวน์โหลดไฟล์ CSV ผ่าน http client ของเฟรมเวิร์ก
 * ใช้ลิงก์ธรรมดาไม่ได้ เพราะ endpoint ต้องการ Authorization header
 *
 * @param {string} url
 * @param {string} filename
 *
 * @return {Promise<void>}
 */
async function omsinDownload(url, filename) {
  try {
    const response = await window.http.get(url, {
      throwOnError: false,
      headers: {'Accept': 'text/csv'}
    });

    if (response && response.success === false) {
      NotificationManager.error(response.message || Now.translate('Unable to complete the transaction'));
      return;
    }

    const payload = response && response.data !== undefined ? response.data : response;
    const blob = payload instanceof Blob ? payload : new Blob([payload], {type: 'text/csv;charset=utf-8'});
    const href = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = href;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(href);
  } catch (error) {
    console.error('omsinDownload', error);
    NotificationManager.error(Now.translate('Unable to complete the transaction'));
  }
}

/**
 * ปุ่มส่งออกข้อมูลทั้งหมด (หน้า นำเข้า/ส่งออก)
 *
 * @return {Promise<void>}
 */
function omsinExport() {
  return omsinDownload('api/omsin/database/export', 'omsin.csv');
}

/**
 * ปุ่มดาวน์โหลดไฟล์ตัวอย่าง (หน้า นำเข้า/ส่งออก)
 *
 * @return {Promise<void>}
 */
function omsinDemo() {
  return omsinDownload('api/omsin/database/demo', 'omsin-demo.csv');
}
