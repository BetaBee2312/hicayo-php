const input = {
  hori: 0, // -1 kiri, 1 kanan
  vert: 0, // 1 atas, -1 bawah
}

let isDropping = false

const elements = {
  clawMachine: document.querySelector('.claw-machine'),
  box: document.querySelector('.box'),
  collectionBox: document.querySelector('.collection-box'),
  collectionArrow: document.querySelector('.collection-arrow'),
  toys: [],
}

const settings = {
  targetToy: null,
  collectedNumber: 0,
}

// 1. Tentukan Multiplier berdasarkan lebar layar
const m = window.innerWidth < 768 ? 1.5 : 2; 

// 2. Definisikan ulang objek toys agar menggunakan nilai m yang baru
const toys = {
  beauty: { w: 32 * m, h: 32 * m },
  bolit: { w: 32 * m, h: 32 * m },
  bread: { w: 32 * m, h: 32 * m },
  buming: { w: 32 * m, h: 32 * m },
  coffee: { w: 32 * m, h: 32 * m },
  fresh: { w: 32 * m, h: 32 * m },
  pahe: { w: 32 * m, h: 32 * m },
  robak: { w: 32 * m, h: 32 * m },
  roti: { w: 32 * m, h: 32 * m },
  teras: { w: 32 * m, h: 32 * m },
  xpress: { w: 32 * m, h: 32 * m },
  yoa: { w: 32 * m, h: 32 * m },
};

const sortedToys = [...Object.keys(toys), ...Object.keys(toys)].sort(
  () => 0.5 - Math.random(),
)

const cornerBuffer = 16

const machineBuffer = {
  x: 36,
  y: 16,
}

const radToDeg = rad => Math.round(rad * (180 / Math.PI))
const calcX = (i, n) => i % n
const calcY = (i, n) => Math.floor(i / n)

const machineWidth = window.innerWidth
const machineHeight = window.innerHeight
const machineTop = 0

const machineTopHeight = 140

const machineBottomHeight = 90
const machineBottomTop = window.innerHeight - 135  // bottom:45px + height:90px

const maxArmLength = machineBottomTop - machineTop - machineBuffer.y

const adjustAngle = angle => {
  const adjustedAngle = angle % 360
  return adjustedAngle < 0 ? adjustedAngle + 360 : adjustedAngle
}

const randomN = (min, max) => {
  return Math.round(min - 0.5 + Math.random() * (max - min + 1))
}

//* classes *//

class Button {
  constructor({ className, action, isLocked, pressAction, releaseAction }) {
    Object.assign(this, {
      el: document.querySelector(`.${className}`),
      isLocked,
    })
    this.el.addEventListener('click', action)
    ;['mousedown', 'touchstart'].forEach(action =>
      this.el.addEventListener(action, pressAction),
    )
    ;['mouseup', 'touchend'].forEach(action =>
      this.el.addEventListener(action, releaseAction),
    )

    if (!isLocked) this.activate()
  }
  activate() {
    this.isLocked = false
    this.el.classList.add('active')
  }
  deactivate() {
    this.isLocked = true
    this.el.classList.remove('active')
  }
}

class WorldObject {
  constructor(props) {
    Object.assign(this, {
      x: 0,
      y: 0,
      z: 0,
      angle: 0,
      transformOrigin: { x: 0, y: 0 },
      interval: null,
      default: {},
      moveWith: [],
      el: props.className && document.querySelector(`.${props.className}`),
      ...props,
    })
    this.setStyles()
    if (props.className) {
      const { width, height } = this.el.getBoundingClientRect()
      this.w = width
      this.h = height
    }
    ;['x', 'y', 'w', 'h'].forEach(key => {
      this.default[key] = this[key]
    })
  }
  setStyles() {
    Object.assign(this.el.style, {
      left: `${this.x}px`,
      top: !this.bottom && `${this.y}px`,
      bottom: this.bottom,
      width: `${this.w}px`,
      height: `${this.h}px`,
      transformOrigin: this.transformOrigin,
    })
    this.el.style.zIndex = this.z
  }
  setClawPos(clawPos) {
    this.clawPos = clawPos
  }
  setTransformOrigin(transformOrigin) {
    this.transformOrigin =
      transformOrigin === 'center'
        ? 'center'
        : `${transformOrigin.x}px ${transformOrigin.y}px`
    this.setStyles()
  }
  handleNext(next) {
    clearInterval(this.interval)
    if (next) next()
  }
  resumeMove({ moveKey, target, moveTime, next }) {
    this.interval = null
    this.move({ moveKey, target, moveTime, next })
  }
  resizeShadow() {
    elements.box.style.setProperty('--scale', 0.5 + this.h / maxArmLength / 2)
  }
  move({ moveKey, target, moveTime, next }) {
    if (this.interval) {
      this.handleNext(next)
    } else {
      const moveTarget = target || this.default[moveKey]
      this.interval = setInterval(() => {
        const distance =
          Math.abs(this[moveKey] - moveTarget) < 25
            ? Math.abs(this[moveKey] - moveTarget)
            : 25
        const increment = this[moveKey] > moveTarget ? -distance : distance
        if (
          increment > 0
            ? this[moveKey] < moveTarget
            : this[moveKey] > moveTarget
        ) {
          this[moveKey] += increment
          this.setStyles()
          if (moveKey === 'h') this.resizeShadow()
          if (this.moveWith.length) {
            this.moveWith.forEach(obj => {
              if (!obj) return
              obj[moveKey === 'h' ? 'y' : moveKey] += increment
              obj.setStyles()
            })
          }
        } else {
          this.handleNext(next)
        }
      }, moveTime || 100)
    }
  }
  distanceBetween(target) {
    return Math.round(
      Math.sqrt(
        Math.pow(this.x - target.x, 2) + Math.pow(this.y - target.y, 2),
      ),
    )
  }
}

class Toy extends WorldObject {
  constructor(props) {
    const toyType = sortedToys[props.index]
    const size = toys[toyType]

    // Zigzag layout: 3 kolom, 2 baris
    // Baris genap (0): posisi normal, baris ganjil (1): geser setengah kolom
    const cols = 3
    const col = props.index % cols
    const row = Math.floor(props.index / cols)
    const colWidth = (machineWidth - cornerBuffer * 2) / cols
    const zigzagOffset = row % 2 === 1 ? colWidth / 2 : 0
    const rowSpacing = size.h + 18

    super({
      el: Object.assign(document.createElement('div'), {
        className: `toy pix ${toyType}`,
      }),
      x: cornerBuffer + col * colWidth + zigzagOffset + colWidth / 2 - size.w / 2 + randomN(-6, 6),
      y: (machineBottomTop - machineTop) - size.h - row * rowSpacing + 15 + randomN(-4, 4),
      z: 0,
      toyType,
      ...size,
      ...props,
    })
    elements.box.append(this.el)
    const toy = this

    this.el.addEventListener('click', () => {
      if (isDropping) return

      // 🎯 set target only
      settings.targetToy = this

      moveClawToToy(this)
    })


    elements.toys.push(this)
  }
  collectToy(toy) {
    toy.el.classList.remove('selected')
    toy.el.classList.remove('grabbed')
    toy.el.classList.remove('falling')
    toy.x = machineWidth / 2 - toy.w / 2
    toy.y = machineHeight / 2 - toy.h / 2
    toy.z = 7
    toy.el.style.setProperty('--rotate-angle', '0deg')
    toy.setTransformOrigin('center')
    toy.el.classList.add('display')
    elements.clawMachine.classList.add('show-overlay')
    settings.collectedNumber++
    elements.collectionBox.appendChild(
      Object.assign(document.createElement('div'), {
        className: `toy-wrapper ${
          settings.collectedNumber > 6 ? 'squeeze-in' : ''
        }`,
        innerHTML: `<div class="toy pix ${toy.toyType}"></div>`,
      }),
    )
    setTimeout(() => {
      elements.clawMachine.classList.remove('show-overlay')
      if (!document.querySelector('.selected'))
        elements.collectionArrow.classList.remove('active')
    }, 1000)
  }
  setRotateAngle() {
    const angle =
      radToDeg(
        Math.atan2(
          this.y + this.h / 2 - this.clawPos.y,
          this.x + this.w / 2 - this.clawPos.x,
        ),
      ) - 90
    const adjustedAngle = Math.round(adjustAngle(angle))
    this.angle =
      adjustedAngle < 180 ? adjustedAngle * -1 : 360 - adjustedAngle
    this.el.style.setProperty('--rotate-angle', `${this.angle}deg`)
  }
}

//* set up *//
elements.box.style.setProperty('--shadow-pos', `${maxArmLength}px`)

const armJoint = new WorldObject({
  className: 'arm-joint',
})

const vertRail = new WorldObject({
  className: 'vert',
  moveWith: [null, armJoint],
})

const arm = new WorldObject({
  className: 'arm',
})

armJoint.resizeShadow()

armJoint.move({
  moveKey: 'y',
  target: machineTopHeight - machineBuffer.y,
  moveTime: 50,
  next: () =>
    vertRail.resumeMove({
      moveKey: 'x',
      target: machineBuffer.x,
      moveTime: 50,
      next: () => {
        Object.assign(armJoint.default, {
          y: machineTopHeight - machineBuffer.y,
          x: machineBuffer.x,
        })
        Object.assign(vertRail.default, {
          x: machineBuffer.x,
        })
        activateHoriBtn()
      },
    }),
})

const doOverlap = (a, b) => {
  return b.x > a.x && b.x < a.x + a.w && b.y > a.y && b.y < a.y + a.h
}

const getClosestToy = () => {
  const claw = {
    // Sesuaikan titik tengah capit agar lebih akurat
    y: armJoint.y + maxArmLength + machineBuffer.y + 3,
    x: armJoint.x + 3,
    w: 32,
    h: 32,
  }

  // Cari boneka yang paling dekat jaraknya dengan pusat capit
  const overlappedToys = elements.toys.filter(t => doOverlap(t, claw));

  if (overlappedToys.length) {
    // SORTIR berdasarkan jarak terdekat (Euclidean distance) 
    // agar capit mengambil yang benar-benar di bawahnya, bukan cuma index terbesar
    const toy = overlappedToys.sort((a, b) => {
      const distA = Math.hypot((a.x + a.w/3) - (claw.x + claw.w/3), (a.y + a.h/3) - (claw.y + claw.h/3));
      const distB = Math.hypot((b.x + b.w/3) - (claw.x + claw.w/3), (b.y + b.h/3) - (claw.y + claw.h/3));
      return distA - distB;
    })[0];

    toy.setTransformOrigin({
      x: claw.x - toy.x,
      y: claw.y - toy.y,
    })
    toy.setClawPos({
      x: claw.x,
      y: claw.y,
    })
    settings.targetToy = toy
  }
}

new Array(5).fill('').forEach((_, i) => {
  new Toy({ index: i })
})

const stopHoriBtnAndActivateVertBtn = () => {
  armJoint.interval = null
  horiBtn.deactivate()
  vertBtn.activate()
}

const activateHoriBtn = () => {
  horiBtn.activate()
  ;[vertRail, armJoint, arm].forEach(c => (c.interval = null))
}

const dropToy = () => {
  arm.el.classList.add('open')
  if (settings.targetToy) {
    settings.targetToy.z = 3
    settings.targetToy.el.classList.remove('grabbed')
    settings.targetToy.el.classList.add('falling')
    settings.targetToy.move({
      moveKey: 'y',
      target: machineHeight - settings.targetToy.h - 28,
      moveTime: 50,
    })
    ;[vertRail, armJoint, arm].forEach(obj => (obj.moveWith[0] = null))
  }
  setTimeout(() => {
    arm.el.classList.remove('open')
    activateHoriBtn()
    if (settings.targetToy) {
    const wonToy = settings.targetToy // snapshot

    wonToy.el.classList.add('selected')
    elements.collectionArrow.classList.add('active')

    // 🎁 REWARD FLOW (safe zone)
    fetchRewardFromDB().then(reward => {
      if (reward) {
        showRewardAlert(reward)
      } else {
        Swal.fire('Oops', 'Hadiah habis 😭', 'warning')
      }
    })

    settings.targetToy = null
  }
  }, 700)
}

const grabToy = () => {
  if (settings.targetToy) {
    ;[vertRail, armJoint, arm].forEach(
      obj => (obj.moveWith[0] = settings.targetToy),
    )
    settings.targetToy.setRotateAngle()
    settings.targetToy.el.classList.add('grabbed')
  } else {
    arm.el.classList.add('missed')
  }
}

const horiBtn = new Button({
  className: 'horizon',
  isLocked: true,
  pressAction: () => {
    input.hori = true
  },
  releaseAction: () => {
    input.hori = false
    stopHoriBtnAndActivateVertBtn()
  },
})

const vertBtn = new Button({
  className: 'vertic',
  isLocked: true,
  pressAction: () => {
    if (vertBtn.isLocked) return
    input.vert = true
  },
  releaseAction: () => {
    input.vert = false
    vertBtn.deactivate()
    // lanjut flow lo (grab logic tetap kepake)
  },
})

const SPEED = 4

function gameLoop() {
  // Horizontal
  if (input.hori !== 0) {
    vertRail.x += input.hori * SPEED
    vertRail.x = Math.max(
      machineBuffer.x,
      Math.min(
        vertRail.x,
        machineWidth - armJoint.w - machineBuffer.x
      )
    )
    vertRail.setStyles()
    armJoint.x = vertRail.x
    armJoint.setStyles()
  }

  // Vertical
  if (input.vert !== 0) {
    armJoint.y -= input.vert * SPEED
    armJoint.y = Math.max(
      machineBuffer.y,
      Math.min(
        armJoint.y,
        maxArmLength
      )
    )
    armJoint.setStyles()
  }

  requestAnimationFrame(gameLoop)
}

requestAnimationFrame(gameLoop)

// Inisialisasi Audio
const sfxMove = new Audio('mp3/robot.mp3');
sfxMove.loop = true; // Suara mesin jalan terus selama bergerak

const sfxClaw = new Audio('mp3/click.mp3'); // Suara jepitan

const handleSpaceDrop = () => {
  if (isDropping) return
  isDropping = true

  getClosestToy()

  setTimeout(() => {
    // --- JEPIT (BUKA) ---
    sfxClaw.play(); 
    arm.el.classList.add('open')

    // 1. CLAW TURUN (GERAK)
    sfxMove.play(); 
    arm.move({
      moveKey: 'h',
      target: maxArmLength,
      next: () => {
        sfxMove.pause(); // Berhenti di bawah

        setTimeout(() => {
          // --- JEPIT (TUTUP) ---
          sfxClaw.play(); 
          arm.el.classList.remove('open')
          grabToy()

          // 3. CLAW NAIK (GERAK)
          sfxMove.play(); 
          arm.resumeMove({
            moveKey: 'h',
            next: () => {
              // 4. GESER KE LUBANG (GERAK)
              // sfxMove tetap play dari langkah sebelumnya
              vertRail.resumeMove({
                moveKey: 'x',
                next: () => {
                  // 5. TURUN & DROP (GERAK)
                  armJoint.resumeMove({
                    moveKey: 'y',
                    next: async () => {
                      sfxMove.pause(); // Berhenti total di lubang
                      
                      const success = !!settings.targetToy
                      dropToy()
                      isDropping = false
                    },
                  })
                },
              })
            },
          })
        }, 500)
      },
    })
  }, 500)
}


const GAME_ID = Number(window.GAME_ID || 0)

const fetchRewardFromDB = async () => {
  if (!GAME_ID) return null
  try {
    const res = await fetch('claim-hadiah.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id_permainan: String(GAME_ID) })
    })
    const result = await res.json()
    if (!res.ok || !result.success) throw new Error(result.message || 'Hadiah gagal diklaim')
    return result.data
  } catch (error) {
    console.error('Gagal mengambil hadiah:', error)
    return null
  }
}

const showRewardAlert = async reward => {
  // Pilih suara berdasarkan kategori
  let audioSrc = '';
  if (reward.kategori === 'utama') {
    audioSrc = 'mp3/champ.mp3';
  } else {
    audioSrc = 'mp3/keprok.mp3';
  }

  // Mainkan suara
  const audio = new Audio(audioSrc);
  audio.play();

  // Tampilkan kembang api
  const firework = () => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js';
    script.onload = () => {
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
      });
    };
    document.body.appendChild(script);
  }

  firework(); // jalankan efek kembang api

  // Tampilkan alert
  Swal.fire({
    icon: 'success',
    title: 'Selamat!! 🎉',
    text: `Kamu Mendapatkan ${reward.nama_hadiah}`,
    showConfirmButton: true,
    confirmButtonText: 'Tutup',
    confirmButtonColor: '#3085d6',
    allowOutsideClick: false,
    allowEscapeKey: false
  }).then(result => {
    if (result.isConfirmed) {
      location.reload();
    }
  });
}

// Inisialisasi Audio
const robotMoveSfx = new Audio('mp3/robot.mp3');
robotMoveSfx.loop = true;

const moveClawToToy = toy => {
  if (isDropping) return;

  // 1. Mulai suara saat robot mulai bergerak di sumbu X
  robotMoveSfx.play().catch(e => console.log("Audio play blocked"));

  const targetX = toy.x + (toy.w / 2) - (armJoint.w / 2);
  const targetY = toy.y - maxArmLength - machineBuffer.y; 

  vertRail.resumeMove({
    moveKey: 'x',
    target: targetX,
    moveTime: 50,
    next: () => {
      // 2. (Opsional) Kamu bisa reset suara atau ubah pitch saat ganti arah ke Y
      robotMoveSfx.playbackRate = 1.1; 
      
      armJoint.resumeMove({
        moveKey: 'y',
        target: targetY,
        next: () => {
          // 3. Berhenti saat sampai di koordinat target sebelum menjepit
          robotMoveSfx.pause();
          robotMoveSfx.currentTime = 0;
          
          handleSpaceDrop();
        },
      })
    },
  })
}

const jingle = new Audio('mp3/jinglev2.mp3');
jingle.loop = true;

let clickTimer = null;

window.addEventListener('click', (e) => {
    // Jika ada klik kedua dalam waktu kurang dari 300ms
    if (e.detail === 2) { 
        // DOUBLE CLICK: Nyalakan Musik
        clearTimeout(clickTimer); // Batalkan aksi single click
        if (jingle.paused) {
            jingle.play()
                .then(() => console.log("Musik NYALA (Double Click)"))
                .catch(err => console.log("Butuh interaksi pertama..."));
        }
    } else if (e.detail === 1) {
        // SINGLE CLICK: Matikan Musik (dengan delay sedikit)
        clickTimer = setTimeout(() => {
            if (!jingle.paused) {
                jingle.pause();
                console.log("Musik MATI (Single Click)");
            }
        }, 250); // Jeda 250ms untuk nunggu apakah ada klik kedua
    }
});