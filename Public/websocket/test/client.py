# encoding: utf-8
from socketIO_client import SocketIO, BaseNamespace
import sys

class Namespace(BaseNamespace):
    def on_connect(self):
        print('[Connected]')

    def on_login(self, *args):
        print 'on_login', args[0]

    def on_new_msg(self, *args):
        print 'on_new_msg', args[0]

    def on_update_online_count(self, *args):
        print 'on_update_online_count', args[0]

socketIO = SocketIO(sys.argv[1], sys.argv[2], Namespace)
socketIO.emit('login', sys.argv[3])
socketIO.wait()
